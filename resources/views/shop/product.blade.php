@extends('layouts.shop')
@use('App\Models\Produk')
@use('App\Support\Fmt')
@section('title', $product['name'])

@section('content')
@php
    $st = Produk::stockStatus($product['stock'], $product['min_stock'] ?? null);
    $out = $product['stock'] < 1;
    $expiryText = ! empty($product['expiry']) ? Fmt::monthYear($product['expiry'], true) : 'Menunggu stok masuk';
@endphp
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
    <nav aria-label="Breadcrumb" class="text-sm text-ink/70">
        <a href="{{ route('shop.catalog') }}" class="underline">Katalog</a> /
        <a href="{{ route('shop.catalog', ['kategori' => $product['category']]) }}" class="underline">{{ $product['category'] }}</a> /
        <span class="font-semibold text-ink">{{ $product['name'] }}</span>
    </nav>

    <div class="mt-5 grid gap-8 md:grid-cols-2">
        <div class="grid min-h-72 place-items-center overflow-hidden rounded-2xl border border-ink/10 bg-mint p-8 md:aspect-square">
            @if (! empty($product['image_url']))
                <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full max-h-80 w-full rounded-xl object-contain">
            @else
                <x-bottle :color="$product['color']" class="h-56 md:h-72" />
            @endif
        </div>

        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-ink/60">{{ $product['category'] }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $product['name'] }}</h1>
            <p class="mt-2 flex flex-wrap items-baseline gap-x-3">
                <span class="text-3xl font-extrabold">{{ Fmt::rp($product['price']) }}</span>
                <span class="text-sm text-ink/70">per botol, isi {{ $product['content'] }}</span>
            </p>
            <p class="mt-3 flex flex-wrap items-center gap-3">
                <x-chip :type="$st['key']">{{ $out ? 'Stok habis' : "Tersedia · {$product['stock']} botol" }}</x-chip>
                <span class="text-sm text-ink/70">Kedaluwarsa {{ $expiryText }}</span>
            </p>
            <p class="mt-4 max-w-prose">{{ $product['description'] }}</p>

            <div class="mt-5 rounded-2xl border border-ink/10 bg-white p-5">
                @if ($out)
                    <p class="font-bold">Stok produk ini sedang habis.</p>
                    <p class="mt-1 text-sm text-ink/70">Pilih produk lain di kategori yang sama, atau cek lagi nanti.</p>
                    <x-button variant="outline" type="button" disabled class="mt-4 w-full">Stok habis</x-button>
                @else
                    <form method="POST" action="{{ route('shop.cart.add') }}" data-totals>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <label for="qty" class="text-sm font-bold">Jumlah</label>
                                <div class="mt-2"><x-qty id="qty" name="qty" :max="min(99, $product['stock'])" :price="$product['price']" data-id="p" /></div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-ink/70">Subtotal</p>
                                <p class="text-2xl font-extrabold sm:text-3xl" data-sum>{{ Fmt::rp($product['price']) }}</p>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <x-button>Tambah ke keranjang</x-button>
                            <x-button variant="accent" name="buy" value="1">Beli sekarang</x-button>
                        </div>
                    </form>
                @endif
            </div>

            <dl class="mt-4 divide-y divide-ink/10 rounded-2xl border border-ink/10 bg-white text-sm">
                @foreach ([['Kategori', $product['category']], ['Isi kemasan', $product['content']], ['Aturan pakai', $product['usage']], ['Kedaluwarsa', $expiryText]] as [$k, $v])
                    <div class="flex gap-4 px-4 py-3"><dt class="w-32 shrink-0 text-ink/70">{{ $k }}</dt><dd class="font-bold">{{ $v }}</dd></div>
                @endforeach
            </dl>

            <p class="mt-4 rounded-xl bg-mint px-4 py-3 text-sm"><strong>Cara bayar:</strong> transfer ke rekening toko, lalu kirim bukti lewat WhatsApp. Admin memverifikasi dan memproses pesanan. Suplemen bukan obat.</p>
        </div>
    </div>

    @if (count($related))
        <section class="mt-12">
            <h2 class="text-[22px] font-extrabold tracking-tight">Produk lain di kategori ini</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($related as $p)
                    <a href="{{ route('shop.product', $p['slug']) }}" class="rounded-2xl border border-ink/10 bg-white p-3 hover:border-brand">
                        <span class="grid h-28 place-items-center overflow-hidden rounded-xl bg-mint">
                            @if (! empty($p['image_url']))
                                <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover">
                            @else
                                <x-bottle :color="$p['color']" class="h-16" />
                            @endif
                        </span>
                        <span class="mt-3 block font-bold">{{ $p['name'] }}</span>
                        <span class="mt-1 block text-sm font-semibold text-ink/80">{{ Fmt::rp($p['price']) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
