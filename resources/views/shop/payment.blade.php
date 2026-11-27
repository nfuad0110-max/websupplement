@extends('layouts.shop')
@use('App\Support\Fmt')
@section('title', 'Pembayaran ' . $order['number'])
@section('steps')<x-steps :current="2" />@endsection

@section('content')
@php
    $c = $order['customer'];
    $lines = [
        'Halo ' . config('supplemart.name') . ', saya sudah transfer.',
        'Pesanan: ' . $order['number'],
        'Nama: ' . $c['nama'],
        collect($order['items'])->map(fn ($i) => $i['name'] . ' × ' . $i['qty'])->implode(', '),
        'Total: ' . Fmt::rp($order['total']),
        'Alamat: ' . $c['alamat'],
    ];
    $waUrl = 'https://wa.me/' . config('supplemart.whatsapp') . '?text=' . rawurlencode(implode("\n", $lines));
    $card = 'rounded-2xl border border-ink/10 bg-white p-5 sm:p-6';
    $num = 'inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-900';
@endphp
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <p class="flex flex-wrap items-center gap-3 text-sm">
        <x-chip type="menunggu" />
        <span class="text-ink/70">Pesanan <strong class="text-ink">{{ $order['number'] }}</strong> · {{ Fmt::dateLong($order['created']) }}</span>
    </p>
    <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Pesanan dibuat. Sekarang transfer dan kirim bukti.</h1>
    <p class="mt-3 max-w-xl text-ink/80">Admin memproses pesanan setelah bukti transfer diterima dan diverifikasi. Pesanan dapat dibatalkan jika pembayaran tidak diterima dalam batas waktu yang ditentukan penjual.</p>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-5">
            {{-- 1. Transfer --}}
            <section class="{{ $card }}">
                <h2 class="flex items-center gap-3 text-xl font-extrabold tracking-tight"><span class="{{ $num }}"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span>1</span>Transfer ke rekening toko</h2>
                <dl class="mt-4 overflow-hidden rounded-xl border border-ink/10 text-sm">
                    <div class="flex gap-4 border-b border-ink/10 px-4 py-3"><dt class="w-32 shrink-0 text-ink/70">Bank</dt><dd class="font-bold">{{ config('supplemart.bank.name') }}</dd></div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink/10 px-4 py-3">
                        <div class="flex gap-4"><dt class="w-32 shrink-0 self-center text-ink/70">Nomor rekening</dt><dd class="text-xl font-extrabold">{{ config('supplemart.bank.number') }}</dd></div>
                        <x-button variant="outline" type="button" :data-copy="config('supplemart.bank.number')">Salin nomor</x-button>
                    </div>
                    <div class="flex gap-4 border-b border-ink/10 px-4 py-3"><dt class="w-32 shrink-0 text-ink/70">Atas nama</dt><dd class="font-bold">{{ config('supplemart.bank.holder') }}</dd></div>
                    <div class="flex items-center gap-4 bg-amber-100 px-4 py-3"><dt class="w-32 shrink-0 font-bold text-amber-900">Jumlah transfer</dt><dd class="text-2xl font-extrabold">{{ Fmt::rp($order['total']) }}</dd></div>
                </dl>
                <p class="mt-3 text-sm text-ink/70">Ongkos kirim dikonfirmasi admin lewat WhatsApp dan dibayar terpisah bila ada.</p>
            </section>

            {{-- 2. WhatsApp --}}
            <section class="{{ $card }}">
                <h2 class="flex items-center gap-3 text-xl font-extrabold tracking-tight"><span class="{{ $num }}"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span>2</span>Kirim bukti lewat WhatsApp</h2>
                <p class="mt-3 text-ink/80">Tombol di bawah membuka WhatsApp dengan pesan yang sudah berisi ringkasan pesanan. Lampirkan foto bukti transfer lalu kirim.</p>
                <div class="mt-4 rounded-xl bg-emerald-100/70 p-4">
                    <div class="max-w-md rounded-xl bg-white p-4 text-sm shadow-sm">
                        <p class="font-bold">{{ $lines[0] }}</p>
                        <p class="mt-1 space-y-0.5 whitespace-pre-line">{{ implode("\n", array_slice($lines, 1)) }}</p>
                    </div>
                </div>
                <x-button variant="wa" :href="$waUrl" target="_blank" rel="noopener" class="mt-4 w-full">
                    <x-icon name="chat" />Buka WhatsApp dan kirim bukti
                </x-button>
            </section>

            {{-- 3. Verifikasi --}}
            <section class="{{ $card }}">
                <h2 class="flex items-center gap-3 text-xl font-extrabold tracking-tight"><span class="{{ $num }}"><span class="h-1.5 w-1.5 rounded-full bg-brand"></span>3</span>Tunggu verifikasi admin</h2>
                <p class="mt-3 flex flex-wrap items-center gap-2" aria-label="Status pesanan">
                    <x-chip type="menunggu" /><span aria-hidden="true">›</span>
                    <x-chip type="netral">Dibayar</x-chip><span aria-hidden="true">›</span>
                    <x-chip type="netral">Dikirim</x-chip><span aria-hidden="true">›</span>
                    <x-chip type="netral">Selesai</x-chip>
                </p>
                <p class="mt-3 text-ink/80">Anda akan dihubungi lewat WhatsApp saat pembayaran terverifikasi dan saat pesanan dikirim.</p>
            </section>
        </div>

        <aside class="{{ $card }} lg:sticky lg:top-20">
            <h2 class="text-xl font-extrabold tracking-tight">Ringkasan pesanan</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($order['items'] as $i)
                    <li class="flex justify-between gap-3"><span>{{ $i['name'] }} × {{ $i['qty'] }}</span><span class="font-bold">{{ Fmt::rp($i['line']) }}</span></li>
                @endforeach
            </ul>
            <p class="mt-3 flex items-baseline justify-between border-t border-ink/10 pt-3"><span class="font-bold">Total produk</span><span class="text-2xl font-extrabold">{{ Fmt::rp($order['total']) }}</span></p>
            <div class="mt-4 border-t border-ink/10 pt-4 text-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-ink/60">Dikirim ke</p>
                <p class="mt-1 font-bold">{{ $c['nama'] }}</p>
                <p class="text-ink/80">{{ $c['whatsapp'] }}</p>
                <p class="text-ink/80">{{ $c['alamat'] }}</p>
            </div>
            <x-button variant="outline" :href="route('shop.catalog')" class="mt-4 w-full">Belanja lagi</x-button>
        </aside>
    </div>
</div>
@endsection
