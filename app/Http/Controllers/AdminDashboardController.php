<?php

namespace App\Http\Controllers;

use App\Models\BatchStok;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Services\FefoStockService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(FefoStockService $fefo): View
    {
        $waiting = Pesanan::query()->where('status', Pesanan::STATUS_MENUNGGU)->count();
        $paid = Pesanan::query()->where('status', Pesanan::STATUS_DIBAYAR)->count();

        $products = Produk::query()
            ->active()
            ->withStockSummary()
            ->get()
            ->map->toViewArray();

        $lowStock = $products
            ->filter(fn (array $p) => $p['stock'] <= $p['min_stock'])
            ->sortBy('stock')
            ->values()
            ->all();

        $nearDays = (int) config('supplemart.near_expiry_days', 90);
        $today = now()->toDateString();
        $cutoff = now()->addDays($nearDays)->toDateString();
        $prioritizedCodes = $fefo->getPrioritizedCodes();

        $nearExpiry = BatchStok::query()
            ->with('produk')
            ->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>', $today)
            ->whereDate('tanggal_kedaluwarsa', '<', $cutoff)
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id')
            ->get()
            ->map(fn (BatchStok $b) => $b->toViewArray($prioritizedCodes))
            ->values()
            ->all();

        $orders = Pesanan::query()
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Pesanan $o) => [
                'number' => $o->kode_pesanan,
                'name' => $o->nama_pemesan,
                'total' => (float) $o->total_produk,
                'status' => $o->statusKey(),
            ])
            ->all();

        return view('admin.dashboard', compact('orders', 'waiting', 'paid', 'lowStock', 'nearExpiry'));
    }
}
