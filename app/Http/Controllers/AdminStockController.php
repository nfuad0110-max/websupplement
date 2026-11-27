<?php

namespace App\Http\Controllers;

use App\Models\BatchStok;
use App\Models\Produk;
use App\Services\FefoStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStockController extends Controller
{
    public function index(FefoStockService $fefo): View
    {
        $prioritizedCodes = $fefo->getPrioritizedCodes();

        $batches = BatchStok::query()
            ->with('produk')
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id')
            ->get()
            ->map(fn (BatchStok $b) => $b->toViewArray($prioritizedCodes))
            ->values()
            ->all();

        $products = Produk::query()
            ->active()
            ->withStockSummary()
            ->orderBy('nama_produk')
            ->get()
            ->map->toViewArray()
            ->values()
            ->all();

        return view('admin.stock', compact('batches', 'products'));
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'product_id' => ['required', 'integer', 'exists:produk,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'received_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:received_at'],
        ], [
            'product_id.required' => 'Pilih produk terlebih dahulu.',
            'quantity.required' => 'Jumlah masuk wajib diisi.',
            'quantity.min' => 'Jumlah masuk minimal 1 unit.',
            'received_at.required' => 'Tanggal masuk wajib diisi.',
            'expires_at.required' => 'Tanggal kedaluwarsa wajib diisi.',
            'expires_at.after' => 'Tanggal kedaluwarsa harus lebih dari tanggal masuk.',
        ]);

        $next = ((int) BatchStok::query()->max('id')) + 1;
        do {
            $kodeBatch = 'B-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = BatchStok::query()
                ->where('produk_id', $data['product_id'])
                ->where('kode_batch', $kodeBatch)
                ->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        BatchStok::query()->create([
            'produk_id' => $data['product_id'],
            'kode_batch' => $kodeBatch,
            'jumlah_masuk' => $data['quantity'],
            'sisa' => $data['quantity'],
            'tanggal_masuk' => $data['received_at'],
            'tanggal_kedaluwarsa' => $data['expires_at'],
            'dicatat_oleh' => auth()->id(),
        ]);

        return redirect()->route('admin.stock')->with('status', "Stok masuk batch {$kodeBatch} berhasil dicatat.");
    }

    public function prioritize(string $code, FefoStockService $fefo): RedirectResponse
    {
        $batch = BatchStok::query()
            ->where('kode_batch', $code)
            ->first();

        if (! $batch && preg_match('/^B-(\d+)$/', $code, $m)) {
            $batch = BatchStok::query()->find((int) $m[1]);
        }

        if (! $batch) {
            return back()->with('error', "Batch {$code} tidak ditemukan.");
        }

        if ($batch->sisa <= 0 || $batch->tanggal_kedaluwarsa->lte(today())) {
            return back()->with('error', "Batch {$code} sudah habis atau kedaluwarsa.");
        }

        $actualCode = $batch->kode_batch ?: ('B-' . str_pad((string) $batch->id, 4, '0', STR_PAD_LEFT));
        $enabled = $fefo->togglePriority($actualCode);

        $msg = $enabled
            ? "Batch {$actualCode} ditandai untuk diprioritaskan jual lebih dulu."
            : "Prioritas jual untuk batch {$actualCode} dilepas (kembali ke urutan FEFO standar).";

        return back()->with('status', $msg);
    }
}
