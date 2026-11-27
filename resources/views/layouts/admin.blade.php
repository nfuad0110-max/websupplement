@extends('layouts.base')

@section('body')
@use('App\Support\Fmt')
@php
    $authUser = auth()->user();
    $role = $authUser?->role ?? ($role ?? 'admin');
    $nav = $role === 'owner'
        ? [['Rekap penjualan', 'owner.report', 'chart', 'owner.report'], ['Lihat toko', 'shop.catalog', 'cart', 'shop.catalog']]
        : [['Dashboard', 'admin.dashboard', 'dashboard', 'admin.dashboard'], ['Produk', 'admin.products', 'box', 'admin.products*'], ['Stok masuk', 'admin.stock', 'inbox', 'admin.stock*'], ['Pesanan', 'admin.orders', 'receipt', 'admin.orders*'], ['Rekap penjualan', 'owner.report', 'chart', 'owner.report']];
    $user = $role === 'owner'
        ? [$authUser?->name ?? 'Pemilik Usaha', 'Pemilik usaha']
        : [$authUser?->name ?? 'Admin Toko', config('supplemart.name')];
@endphp

<div class="lg:flex lg:min-h-screen">
    {{-- Sidebar (desktop) --}}
    <aside class="hidden w-56 shrink-0 flex-col bg-brand-dark p-4 text-white lg:sticky lg:top-0 lg:flex lg:h-screen">
        <a href="{{ route($nav[0][1]) }}" class="px-2 py-3" aria-label="SUPPLEMART"><x-logo :dark="true" /></a>
        <nav aria-label="Menu admin" class="mt-6 space-y-1.5">
            @foreach ($nav as [$label, $route, $icon, $match])
                @php $active = request()->routeIs($match); @endphp
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold {{ $active ? 'bg-white/15' : 'hover:bg-white/10' }}">
                    <x-icon :name="$icon" />{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-xl bg-white/10 p-4">
            <p class="font-bold">{{ $user[0] }}</p>
            <p class="text-xs text-white/80">{{ $user[1] }}</p>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="inline-block cursor-pointer text-sm font-bold underline">Keluar</button>
            </form>
        </div>
    </aside>

    {{-- Bilah atas (HP dan tablet) --}}
    <header class="bg-brand-dark text-white lg:hidden">
        <div class="flex items-center justify-between px-4 py-3">
            <a href="{{ route($nav[0][1]) }}" aria-label="SUPPLEMART"><x-logo :dark="true" /></a>
            <div class="flex items-center gap-3 text-sm">
                <span class="font-semibold">{{ $user[0] }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer font-bold underline">Keluar</button>
                </form>
            </div>
        </div>
        <nav aria-label="Menu admin" class="flex gap-1.5 overflow-x-auto px-3 pb-3">
            @foreach ($nav as [$label, $route, $icon, $match])
                @php $active = request()->routeIs($match); @endphp
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-3.5 text-sm font-semibold {{ $active ? 'bg-white/15' : 'hover:bg-white/10' }}">
                    <x-icon :name="$icon" class="h-4 w-4" />{{ $label }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-ink/60">@yield('eyebrow')</p>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">@yield('title')</h1>
            </div>
            <div class="flex flex-wrap gap-2">@yield('actions')</div>
        </div>

        @if (session('status') || session('error'))
            <div class="mb-6 space-y-2"><x-flash /></div>
        @endif

        @yield('content')
    </main>
</div>
@endsection
