@extends('layouts.admin')
@use('App\Models\Produk')
@use('App\Support\Fmt')
@section('eyebrow', Fmt::dayDateLong(now()))
@section('title', 'Dashboard')
@section('actions')<x-button variant="accent" :href="route('admin.stock')">Catat stok masuk</x-button>@endsection

@section('content')
@php
    $card = 'rounded-2xl border border-ink/10 bg-white';
    $th = 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-ink/60';
@endphp

<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ([
        ['Perlu verifikasi pembayaran', $waiting, 'menunggu', 'Menunggu pembayaran'],
        ['Perlu dikirim', $paid, 'dibayar', 'Dibayar'],
        ['Stok menipis atau habis', count($lowStock), 'habis', 'Perlu tambah stok'],
        ['Hampir kedaluwarsa (< ' . config('supplemart.near_expiry_days', 90) . ' hari)', count($nearExpiry), 'hampir', 'Perlu ditindaklanjuti'],
    ] as [$label, $n, $type, $text])
        <div class="{{ $card }} p-5">
            <p class="text-sm text-ink/70">{{ $label }}</p>
            <p class="mt-2 text-4xl font-extrabold">{{ $n }}</p>
            <x-chip :type="$type" class="mt-2">{{ $text }}</x-chip>
        </div>
    @endforeach
</div>

<div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_320px]">
    <section class="{{ $card }} overflow-hidden">
        <div class="p-5">
            <h2 class="text-[22px] font-extrabold tracking-tight">Hampir kedaluwarsa</h2>
            <p class="text-sm text-ink/70">Urut dari yang paling cepat. Jual batch ini lebih dulu.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[34rem] text-sm">
                <thead class="bg-mint"><tr><th class="{{ $th }}">Produk · Batch</th><th class="{{ $th }}">Sisa</th><th class="{{ $th }}">Kedaluwarsa</th><th class="{{ $th }}">Sisa waktu</th><th class="{{ $th }}"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse ($nearExpiry as $b)
                        <tr>
                            <td class="px-4 py-3"><p class="font-bold">{{ $b['product']['name'] }}</p><p class="text-xs text-ink/70">Batch {{ $b['code'] }}</p></td>
                            <td class="px-4 py-3 font-bold">{{ $b['left'] }}</td>
                            <td class="px-4 py-3">{{ Fmt::date($b['expiry']) }}</td>
                            <td class="px-4 py-3"><x-chip :type="$b['state']['key']">{{ $b['days'] }} hari</x-chip></td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.stock.priority', $b['code']) }}">
                                    @csrf
                                    <x-button :variant="! empty($b['prioritized']) ? 'primary' : 'outline'">
                                        {{ ! empty($b['prioritized']) ? 'Diprioritaskan' : 'Prioritaskan jual' }}
                                    </x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink/70">Tidak ada batch yang hampir kedaluwarsa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="{{ $card }}">
        <div class="p-5">
            <h2 class="text-[22px] font-extrabold tracking-tight">Stok menipis</h2>
            <p class="text-sm text-ink/70">Batas minimum {{ config('supplemart.low_stock') }} unit.</p>
        </div>
        <ul class="divide-y divide-ink/10 border-t border-ink/10">
            @forelse ($lowStock as $p)
                @php $st = Produk::stockStatus($p['stock'], $p['min_stock'] ?? null); @endphp
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div><p class="font-bold">{{ $p['name'] }}</p><p class="text-xs text-ink/70">Sisa {{ $p['stock'] }} unit</p></div>
                    <x-chip :type="$st['key']">{{ $p['stock'] === 0 ? 'Stok habis' : 'Menipis' }}</x-chip>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-ink/70">Semua stok di atas batas minimum.</li>
            @endforelse
        </ul>
        <div class="p-5"><x-button variant="outline" :href="route('admin.stock')" class="w-full">Tambah stok</x-button></div>
    </section>
</div>

<section class="{{ $card }} mt-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 p-5">
        <h2 class="text-[22px] font-extrabold tracking-tight">Pesanan terbaru</h2>
        <x-button variant="outline" :href="route('admin.orders')">Kelola semua pesanan</x-button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[30rem] text-sm">
            <thead class="bg-mint"><tr><th class="{{ $th }}">No.</th><th class="{{ $th }}">Pemesan</th><th class="{{ $th }} text-right">Total</th><th class="{{ $th }}">Status</th></tr></thead>
            <tbody class="divide-y divide-ink/10">
                @forelse ($orders as $o)
                    <tr>
                        <td class="px-4 py-3 font-bold"><a href="{{ route('admin.orders', ['no' => $o['number']]) }}" class="hover:underline">{{ $o['number'] }}</a></td>
                        <td class="px-4 py-3">{{ $o['name'] }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ Fmt::rp($o['total']) }}</td>
                        <td class="px-4 py-3"><x-chip :type="$o['status']" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-ink/70">Belum ada pesanan masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
