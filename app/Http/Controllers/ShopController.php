<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Kategori;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RiwayatStatusPesanan;
use App\Services\FefoStockService;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ShopController extends Controller
{
    public function catalog(Request $r): View
    {
        $categories = Kategori::query()->orderBy('id')->pluck('nama_kategori')->all();

        $query = Produk::query()
            ->active()
            ->withStockSummary()
            ->orderBy('id');

        if ($r->filled('kategori')) {
            $kategori = (string) $r->input('kategori');
            $query->whereHas('kategori', fn ($q) => $q->where('nama_kategori', $kategori));
        }

        if ($r->filled('q')) {
            $keyword = trim((string) $r->input('q'));
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama_produk', 'like', "%{$keyword}%")
                    ->orWhere('deskripsi', 'like', "%{$keyword}%");
            });
        }

        $products = $query->get()->map->toViewArray()->values()->all();

        return view('shop.catalog', compact('products', 'categories'));
    }

    public function product(string $slug): View
    {
        $model = Produk::query()
            ->active()
            ->withStockSummary()
            ->where('slug', $slug)
            ->firstOrFail();

        $product = $model->toViewArray();

        $related = Produk::query()
            ->active()
            ->withStockSummary()
            ->where('kategori_id', $model->kategori_id)
            ->where('id', '!=', $model->id)
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->map->toViewArray()
            ->values()
            ->all();

        return view('shop.product', compact('product', 'related'));
    }

    public function cart(): View
    {
        return view('shop.cart', [
            'items' => Cart::items(),
        ]);
    }

    public function addToCart(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Produk::query()
            ->active()
            ->withStockSummary()
            ->findOrFail($data['product_id']);

        if ($product->availableStock() < 1) {
            return back()->with('error', "{$product->nama_produk} sedang habis.");
        }

        Cart::add($product->id, (int) ($data['qty'] ?? 1));

        return $r->boolean('buy')
            ? redirect()->route('shop.cart')
            : back()->with('status', "{$product->nama_produk} masuk keranjang.");
    }

    public function removeFromCart(int $id): RedirectResponse
    {
        Cart::remove($id);

        return back()->with('status', 'Produk dihapus dari keranjang.');
    }

    public function storeOrder(Request $r, FefoStockService $fefo): RedirectResponse
    {
        foreach ((array) $r->input('qty', []) as $id => $qty) {
            Cart::set((int) $id, (int) $qty);
        }

        $data = $r->validate([
            'nama' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'regex:/^(\+62|62|0)8[0-9\-\s]{7,14}$/'],
            'alamat' => ['required', 'string', 'max:300'],
            'catatan' => ['nullable', 'string', 'max:200'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Gunakan nomor WhatsApp yang valid, misalnya 0812-3456-7801.',
            'alamat.required' => 'Alamat wajib diisi.',
        ]);

        $items = Cart::items();
        if (! $items) {
            return redirect()->route('shop.cart')->with('error', 'Keranjang masih kosong.');
        }

        try {
            $pesanan = DB::transaction(function () use ($data, $items, $fefo): Pesanan {
                $nextNum = ((int) Pesanan::query()->lockForUpdate()->max('id')) + 1;
                do {
                    $kode = 'SM-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
                    $exists = Pesanan::query()->where('kode_pesanan', $kode)->exists();
                    if ($exists) {
                        $nextNum++;
                    }
                } while ($exists);

                $totalProduk = 0.0;
                $prepared = [];

                foreach ($items as $item) {
                    $produk = Produk::query()->active()->find($item['product']['id']);
                    if (! $produk) {
                        throw new RuntimeException("{$item['product']['name']} sudah tidak tersedia.");
                    }

                    $qty = (int) $item['qty'];
                    $harga = (float) $produk->harga;
                    $totalProduk += $harga * $qty;

                    $prepared[] = [
                        'produk' => $produk,
                        'qty' => $qty,
                        'harga' => $harga,
                    ];
                }

                $hours = (int) config('supplemart.payment_deadline_hours', 24);

                $pesanan = Pesanan::query()->create([
                    'kode_pesanan' => $kode,
                    'nama_pemesan' => $data['nama'],
                    'no_whatsapp' => $data['whatsapp'],
                    'alamat_pengiriman' => $data['alamat'],
                    'catatan_pembeli' => $data['catatan'] ?? null,
                    'total_produk' => $totalProduk,
                    'ongkos_kirim' => 0,
                    'status' => Pesanan::STATUS_MENUNGGU,
                    'batas_waktu_bayar' => now()->addHours($hours),
                ]);

                foreach ($prepared as $row) {
                    /** @var Produk $produk */
                    $produk = $row['produk'];
                    $detail = DetailPesanan::query()->create([
                        'pesanan_id' => $pesanan->id,
                        'produk_id' => $produk->id,
                        'nama_produk' => $produk->nama_produk,
                        'harga_satuan' => $row['harga'],
                        'jumlah' => $row['qty'],
                    ]);

                    $fefo->allocateAndDeduct($detail, $row['qty']);
                }

                Pembayaran::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'jumlah_transfer' => $totalProduk,
                    'metode' => 'Transfer bank',
                    'status_verifikasi' => Pembayaran::STATUS_MENUNGGU,
                ]);

                RiwayatStatusPesanan::query()->create([
                    'pesanan_id' => $pesanan->id,
                    'status_lama' => null,
                    'status_baru' => Pesanan::STATUS_MENUNGGU,
                    'diubah_oleh' => null,
                    'catatan' => 'Pesanan dibuat oleh pelanggan melalui web',
                ]);

                return $pesanan;
            });
        } catch (RuntimeException $e) {
            return redirect()->route('shop.cart')->with('error', $e->getMessage());
        }

        $allowed = session('my_orders', []);
        $allowed[] = $pesanan->kode_pesanan;
        session(['my_orders' => array_values(array_unique($allowed))]);

        Cart::clear();

        return redirect()->route('shop.payment', $pesanan->kode_pesanan);
    }

    public function payment(string $number): View|RedirectResponse
    {
        $allowed = session('my_orders', []);
        if (! in_array($number, $allowed, true) && ! auth()->check()) {
            return redirect()->route('shop.catalog');
        }

        $pesanan = Pesanan::query()
            ->with('details')
            ->where('kode_pesanan', $number)
            ->first();

        if (! $pesanan) {
            return redirect()->route('shop.catalog');
        }

        $order = [
            'number' => $pesanan->kode_pesanan,
            'created' => $pesanan->created_at?->toDateString() ?? now()->toDateString(),
            'status' => $pesanan->statusKey(),
            'customer' => [
                'nama' => $pesanan->nama_pemesan,
                'whatsapp' => $pesanan->no_whatsapp,
                'alamat' => $pesanan->alamat_pengiriman,
                'catatan' => $pesanan->catatan_pembeli,
            ],
            'items' => $pesanan->details->map(fn (DetailPesanan $d) => [
                'name' => $d->nama_produk,
                'qty' => (int) $d->jumlah,
                'line' => (float) ($d->subtotal ?? ($d->harga_satuan * $d->jumlah)),
            ])->all(),
            'total' => (float) $pesanan->total_produk,
        ];

        return view('shop.payment', compact('order'));
    }
}
