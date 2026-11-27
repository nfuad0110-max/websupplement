@extends('layouts.base')

@section('body')
@use('App\Support\Cart')
@php
    $link = 'rounded-lg px-3.5 py-2.5 hover:bg-mint';
    $on = 'bg-mint text-brand';
@endphp
<header class="sticky top-0 z-30 border-b border-ink/10 bg-white">
    <div class="mx-auto flex min-h-[60px] max-w-6xl items-center gap-3 px-4 sm:px-6">
        <a href="{{ route('shop.catalog') }}" aria-label="SUPPLEMART, ke katalog"><x-logo /></a>

        <nav aria-label="Menu utama" class="ml-2 hidden items-center gap-1 text-sm font-semibold sm:flex">
            <a href="{{ route('shop.catalog') }}" class="{{ $link }} {{ request()->routeIs('shop.catalog', 'shop.product') ? $on : '' }}">Katalog</a>
            @hasSection('steps')
                @if (request()->routeIs('shop.cart'))
                    <a href="{{ route('shop.cart') }}" class="{{ $link }} {{ $on }}" aria-current="page">Keranjang</a>
                @endif
            @else
                <a href="{{ route('shop.catalog') }}#cara-pesan" class="{{ $link }}">Cara pesan</a>
                <a href="{{ route('admin.login') }}" class="{{ $link }}">Masuk admin</a>
            @endif
        </nav>

        @hasSection('steps')
            <div class="ml-auto">@yield('steps')</div>
        @else
            <a href="{{ route('shop.cart') }}" class="ml-auto inline-flex min-h-11 items-center gap-2 rounded-xl border border-ink/20 bg-white px-3.5 text-sm font-bold hover:bg-mint sm:px-4">
                <x-icon name="cart" />
                <span class="hidden sm:inline">Keranjang</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-900" aria-label="{{ Cart::count() }} barang">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>{{ Cart::count() }}
                </span>
            </a>
        @endif
    </div>
</header>

@if (session('status') || session('error'))
    <div class="mx-auto max-w-6xl space-y-2 px-4 pt-4 sm:px-6"><x-flash /></div>
@endif

<main>@yield('content')</main>

<footer class="mt-16 bg-brand-dark text-white">
    <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-8 sm:px-6 md:flex-row md:justify-between">
        <div>
            <p class="font-bold">{{ config('supplemart.name') }}</p>
            <p class="mt-1 text-sm text-white/80">{{ config('supplemart.address') }}</p>
            <p class="mt-3 flex gap-4 text-sm font-semibold sm:hidden">
                <a href="{{ route('shop.catalog') }}#cara-pesan" class="underline">Cara pesan</a>
                <a href="{{ route('admin.login') }}" class="underline">Masuk admin</a>
            </p>
        </div>
        <div class="text-sm md:text-right">
            <p class="text-white/90">Pembayaran: transfer rekening, konfirmasi via WhatsApp.</p>
            <p class="mt-1 text-xs text-white/80">Suplemen bukan obat. Ikuti aturan pakai pada kemasan.</p>
        </div>
    </div>
</footer>
@endsection
