<?php

namespace App\Services;

use App\Models\AlokasiStokPesanan;
use App\Models\BatchStok;
use App\Models\DetailPesanan;
use App\Models\Pengaturan;
use App\Models\Pesanan;
use App\Models\RiwayatStatusPesanan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FefoStockService
{
    public const PRIORITY_SETTING_KEY = 'prioritized_batches';

    /**
     * @return array<int, string>
     */
    public function getPrioritizedCodes(): array
    {
        $raw = Pengaturan::getValue(self::PRIORITY_SETTING_KEY, '[]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    public function setPriority(string $code, bool $prioritize = true): void
    {
        $codes = $this->getPrioritizedCodes();
        if ($prioritize && ! in_array($code, $codes, true)) {
            $codes[] = $code;
        } elseif (! $prioritize) {
            $codes = array_values(array_filter($codes, fn (string $c) => $c !== $code));
        }

        Pengaturan::setValue(
            self::PRIORITY_SETTING_KEY,
            json_encode($codes, JSON_UNESCAPED_UNICODE),
            'Daftar kode batch yang diprioritaskan untuk dijual lebih dulu'
        );
    }

    public function togglePriority(string $code): bool
    {
        $codes = $this->getPrioritizedCodes();
        $currentlyPrioritized = in_array($code, $codes, true);
        $this->setPriority($code, ! $currentlyPrioritized);

        return ! $currentlyPrioritized;
    }

    /**
     * Alokasi & pengurangan stok berdasarkan FEFO (First Expired First Out)
     * di dalam transaksi database yang sudah aktif.
     */
    public function allocateAndDeduct(DetailPesanan $detail, int $qtyNeeded): void
    {
        $today = now()->toDateString();
        $prioritizedCodes = $this->getPrioritizedCodes();

        $batches = BatchStok::query()
            ->where('produk_id', $detail->produk_id)
            ->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>', $today)
            ->orderBy('tanggal_kedaluwarsa', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        // Utamakan batch yang ditandai prioritas, tetap urut FEFO di masing-masing grup
        if ($prioritizedCodes) {
            $batches = $batches->sortBy(function (BatchStok $batch) use ($prioritizedCodes): string {
                $code = $batch->kode_batch ?: ('B-' . str_pad((string) $batch->id, 4, '0', STR_PAD_LEFT));
                $isPriority = in_array($code, $prioritizedCodes, true) ? '0' : '1';

                return $isPriority . '-' . $batch->tanggal_kedaluwarsa->format('Y-m-d') . '-' . str_pad((string) $batch->id, 10, '0', STR_PAD_LEFT);
            })->values();
        }

        $totalAvailable = (int) $batches->sum('sisa');
        if ($totalAvailable < $qtyNeeded) {
            throw new RuntimeException("Stok {$detail->nama_produk} tidak mencukupi (tersedia {$totalAvailable}, diminta {$qtyNeeded}).");
        }

        $remaining = $qtyNeeded;
        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $batch->sisa, $remaining);
            if ($take <= 0) {
                continue;
            }

            $batch->sisa = (int) $batch->sisa - $take;
            $batch->save();

            AlokasiStokPesanan::query()->create([
                'detail_pesanan_id' => $detail->id,
                'batch_stok_id' => $batch->id,
                'jumlah' => $take,
            ]);

            if ($batch->sisa === 0 && $batch->kode_batch) {
                $this->setPriority($batch->kode_batch, false);
            }

            $remaining -= $take;
        }
    }

    /**
     * Kembalikan seluruh stok yang dialokasikan untuk pesanan saat pesanan dibatalkan.
     */
    public function restoreOrderStock(Pesanan $pesanan): void
    {
        $details = $pesanan->details()
            ->with(['alokasiStok' => fn ($q) => $q->whereNull('dikembalikan_at')])
            ->get();

        foreach ($details as $detail) {
            foreach ($detail->alokasiStok as $alokasi) {
                /** @var BatchStok|null $batch */
                $batch = BatchStok::query()
                    ->whereKey($alokasi->batch_stok_id)
                    ->lockForUpdate()
                    ->first();

                if ($batch) {
                    $batch->sisa = min((int) $batch->jumlah_masuk, (int) $batch->sisa + (int) $alokasi->jumlah);
                    $batch->save();
                }

                $alokasi->dikembalikan_at = now();
                $alokasi->save();
            }
        }
    }

    /**
     * Batalkan otomatis pesanan belum dibayar yang melewati batas_waktu_bayar.
     */
    public function cancelExpiredOrders(): int
    {
        $expiredIds = Pesanan::query()
            ->where('status', Pesanan::STATUS_MENUNGGU)
            ->whereNotNull('batas_waktu_bayar')
            ->where('batas_waktu_bayar', '<=', now())
            ->pluck('id');

        $cancelled = 0;

        foreach ($expiredIds as $id) {
            DB::transaction(function () use ($id, &$cancelled): void {
                $pesanan = Pesanan::query()->whereKey($id)->lockForUpdate()->first();
                if (! $pesanan || $pesanan->status !== Pesanan::STATUS_MENUNGGU) {
                    return;
                }

                $this->restoreOrderStock($pesanan);

                $oldStatus = $pesanan->status;
                $pesanan->update([
                    'status' => Pesanan::STATUS_DIBATALKAN,
                    'dibatalkan_at' => now(),
                    'alasan_pembatalan' => 'Melewati batas waktu pembayaran',
                ]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => Pesanan::STATUS_DIBATALKAN,
                    'diubah_oleh' => null,
                    'catatan' => 'Dibatalkan otomatis karena melewati batas waktu pembayaran',
                ]);

                $cancelled++;
            });
        }

        return $cancelled;
    }
}
