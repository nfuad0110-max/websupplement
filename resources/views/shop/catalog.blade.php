@extends('layouts.shop')
@use('App\Support\Cart')
@use('App\Support\Fmt')
@section('title', 'Katalog')

@section('content')
<section class="bg-brand text-white">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 md:py-14">
        <p class="text-xs font-bold uppercase tracking-widest text-white/80">{{ config('supplemart.name') }}</p>
        <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Suplemen sehat,<br>langsung dari pemasok.</h1>
        <p class="mt-3 hidden max-w-lg text-white/90 sm:block">Lihat harga dan stok, pilih produk, lalu pesan tanpa membuat akun. Bayar lewat transfer dan kirim bukti via WhatsApp.</p>

        <form role="search" action="{{ route('shop.catalog') }}" method="GET" class="mt-6 max-w-xl">
            @if (request('kategori'))<input type="hidden" name="kategori" value="{{ request('kategori') }}">@endif
            <label for="q" class="sr-only">Cari produk</label>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink/60" />
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama produk, misalnya vitamin C"
                       class="h-12 w-full rounded-xl border-0 bg-white pl-12 pr-4 text-ink placeholder:text-ink/60 focus-visible:outline-accent">
            </div>
        </form>

        <div class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" role="group" aria-label="Kategori">
            @foreach (array_merge(['Semua'], $categories) as $cat)
                @php $active = $cat === 'Semua' ? ! request('kategori') : request('kategori') === $cat; @endphp
                <a href="{{ route('shop.catalog', array_filter(['kategori' => $cat === 'Semua' ? null : $cat, 'q' => request('q')])) }}"
                   @if ($active) aria-current="true" @endif
                   class="inline-flex min-h-11 shrink-0 items-center rounded-full border px-5 text-sm font-semibold {{ $active ? 'border-white bg-brand-dark text-white' : 'border-white bg-white text-ink hover:bg-mint' }}">{{ $cat }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- Cara pesan: urutan langkah yang nyata --}}
<section id="cara-pesan" class="scroll-mt-16 border-b border-ink/10 bg-white">
    <ol class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 text-sm sm:px-6 md:flex-row md:gap-8">
        @foreach ([['Pilih produk', ' dan isi data pemesan'], ['Transfer', ' ke rekening toko'], ['Kirim bukti', ' lewat WhatsApp, admin memverifikasi']] as $i => $s)
            <li class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-900"><span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>{{ $i + 1 }}</span>
                <span><strong>{{ $s[0] }}</strong>{{ $s[1] }}</span>
            </li>
        @endforeach
    </ol>
</section>

<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 {{ Cart::count() ? 'pb-28 md:pb-8' : '' }}">
    <h2 class="text-[22px] font-extrabold tracking-tight">
        {{ request('kategori') ?: 'Semua produk' }} <span class="text-base font-medium text-ink/70">{{ count($products) }} produk</span>
    </h2>

    @if (count($products))
        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    @else
        <div class="mt-5 rounded-2xl border border-ink/10 bg-white p-10 text-center">
            <p class="font-bold">Produk tidak ditemukan.</p>
            <p class="mt-1 text-sm text-ink/70">Periksa ejaan atau pilih kategori lain.</p>
            <x-button :href="route('shop.catalog')" class="mt-4">Lihat semua produk</x-button>
        </div>
    @endif
</section>

{{-- Bilah keranjang tetap di HP --}}
@if (Cart::count() > 0)
    <div class="fixed inset-x-0 bottom-0 z-20 flex items-center justify-between gap-4 border-t border-ink/10 bg-white px-4 py-3 md:hidden">
        <div>
            <p class="text-xs text-ink/70">{{ Cart::count() }} barang</p>
            <p class="text-xl font-extrabold">{{ Fmt::rp(Cart::total()) }}</p>
        </div>
        <x-button variant="accent" :href="route('shop.cart')">Lihat keranjang</x-button>
    </div>
@endif
@endsection
