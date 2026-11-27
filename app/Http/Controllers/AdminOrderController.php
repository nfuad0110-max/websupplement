<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\RiwayatStatusPesanan;
use App\Services\FefoStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $r): View
    {
        $allModels = Pesanan::query()
            ->with(['details', 'pembayaran'])
            ->latest('id')
            ->get();

        $all = $allModels->map(function (Pesanan $o): array {
            return [
                'number' => $o->kode_pesanan,
                'name' => $o->nama_pemesan,
                'phone' => $o->no_whatsapp,
                'address' => $o->alamat_pengiriman,
                'created' => $o->created_at?->format('d M, H.i') ?? '-',
                'status' => $o->statusKey(),
                'items' => $o->details->map(fn (DetailPesanan $d) => [
                    'name' => $d->nama_produk,
                    'qty' => (int) $d->jumlah,
                    'line' => (float) ($d->subtotal ?? ($d->harga_satuan * $d->jumlah)),
                ])->all(),
                'total' => (float) $o->total_produk,
                'proof' => $o->pembayaran !== null && $o->pembayaran->status_verifikasi !== Pembayaran::STATUS_DITOLAK,
                'payment_status' => $o->pembayaran?->status_verifikasi,
            ];
        });

        $status = $r->query('status');
        $list = $status ? $all->where('status', $status)->values() : $all;
        $selected = $list->firstWhere('number', $r->query('no')) ?? $list->first();

        return view('admin.orders', [
            'list' => $list->all(),
            'selected' => $selected,
            'status' => $status,
            'counts' => $all->countBy('status')->all(),
            'total' => $all->count(),
        ]);
    }

    public function update(Request $r, string $number, FefoStockService $fefo): RedirectResponse
    {
        $data = $r->validate([
            'aksi' => ['required', 'in:valid,tidak_valid,kirim,selesai,batal'],
        ]);

        $aksi = $data['aksi'];

        $result = DB::transaction(function () use ($number, $aksi, $fefo): array {
            $pesanan = Pesanan::query()
                ->where('kode_pesanan', $number)
                ->lockForUpdate()
                ->first();

            if (! $pesanan) {
                return ['ok' => false, 'msg' => "Pesanan {$number} tidak ditemukan."];
            }

            $oldStatus = $pesanan->status;
            $adminId = auth()->id();

            if ($aksi === 'valid') {
                if ($pesanan->status !== Pesanan::STATUS_MENUNGGU) {
                    return ['ok' => false, 'msg' => "Pesanan {$number} tidak dalam status menunggu pembayaran."];
                }

                Pembayaran::query()->updateOrCreate(
                    ['pesanan_id' => $pesanan->id],
                    [
                        'jumlah_transfer' => max(1, (float) $pesanan->total_produk),
                        'metode' => 'Transfer bank',
                        'status_verifikasi' => Pembayaran::STATUS_VALID,
                        'catatan_verifikasi' => 'Pembayaran diverifikasi valid oleh admin',
                        'diverifikasi_oleh' => $adminId,
                        'diverifikasi_at' => now(),
                    ]
                );

                $pesanan->update([
                    'status' => Pesanan::STATUS_DIBAYAR,
                    'dibayar_at' => now(),
                    'diproses_oleh' => $adminId,
                ]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => Pesanan::STATUS_DIBAYAR,
                    'diubah_oleh' => $adminId,
                    'catatan' => 'Pembayaran diverifikasi valid',
                ]);

                return ['ok' => true, 'msg' => "{$number}: Pembayaran diverifikasi"];
            }

            if ($aksi === 'tidak_valid') {
                if ($pesanan->status !== Pesanan::STATUS_MENUNGGU) {
                    return ['ok' => false, 'msg' => "Pesanan {$number} tidak dalam status menunggu pembayaran."];
                }

                Pembayaran::query()->updateOrCreate(
                    ['pesanan_id' => $pesanan->id],
                    [
                        'jumlah_transfer' => max(1, (float) $pesanan->total_produk),
                        'metode' => 'Transfer bank',
                        'status_verifikasi' => Pembayaran::STATUS_DITOLAK,
                        'catatan_verifikasi' => 'Bukti transfer ditandai tidak valid oleh admin',
                        'diverifikasi_oleh' => $adminId,
                        'diverifikasi_at' => now(),
                    ]
                );

                $pesanan->update(['diproses_oleh' => $adminId]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => $oldStatus,
                    'diubah_oleh' => $adminId,
                    'catatan' => 'Bukti transfer ditandai tidak valid',
                ]);

                return ['ok' => true, 'msg' => "{$number}: Bukti ditandai tidak valid"];
            }

            if ($aksi === 'kirim') {
                if ($pesanan->status !== Pesanan::STATUS_DIBAYAR) {
                    return ['ok' => false, 'msg' => "Pesanan {$number} harus berstatus Dibayar sebelum dikirim."];
                }

                $pesanan->update([
                    'status' => Pesanan::STATUS_DIKIRIM,
                    'dikirim_at' => now(),
                    'diproses_oleh' => $adminId,
                ]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => Pesanan::STATUS_DIKIRIM,
                    'diubah_oleh' => $adminId,
                    'catatan' => 'Pesanan ditandai dikirim',
                ]);

                return ['ok' => true, 'msg' => "{$number}: Pesanan ditandai dikirim"];
            }

            if ($aksi === 'selesai') {
                if ($pesanan->status !== Pesanan::STATUS_DIKIRIM) {
                    return ['ok' => false, 'msg' => "Pesanan {$number} harus berstatus Dikirim sebelum diselesaikan."];
                }

                $pesanan->update([
                    'status' => Pesanan::STATUS_SELESAI,
                    'selesai_at' => now(),
                    'diproses_oleh' => $adminId,
                ]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => Pesanan::STATUS_SELESAI,
                    'diubah_oleh' => $adminId,
                    'catatan' => 'Pesanan selesai',
                ]);

                return ['ok' => true, 'msg' => "{$number}: Pesanan selesai"];
            }

            // batal
            if (! in_array($pesanan->status, [Pesanan::STATUS_MENUNGGU, Pesanan::STATUS_DIBAYAR], true)) {
                return ['ok' => false, 'msg' => "Pesanan {$number} tidak dapat dibatalkan."];
            }

            $fefo->restoreOrderStock($pesanan);

            $pesanan->update([
                'status' => Pesanan::STATUS_DIBATALKAN,
                'dibatalkan_at' => now(),
                'alasan_pembatalan' => 'Dibatalkan oleh admin',
                'diproses_oleh' => $adminId,
            ]);

            RiwayatStatusPesanan::query()->create([
                'pesanan_id' => $pesanan->id,
                'status_lama' => $oldStatus,
                'status_baru' => Pesanan::STATUS_DIBATALKAN,
                'diubah_oleh' => $adminId,
                'catatan' => 'Pesanan dibatalkan dan stok batch dikembalikan',
            ]);

            return ['ok' => true, 'msg' => "{$number}: Pesanan dibatalkan dan stok dikembalikan"];
        });

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }
}
