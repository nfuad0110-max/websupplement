@extends('layouts.admin')
@use('App\Support\Fmt')
@section('eyebrow', 'Pesanan pelanggan')
@section('title', 'Pesanan')

@section('content')
@php
    $card = 'rounded-2xl border border-ink/10 bg-white';
    $th = 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-ink/60';
    $tabs = [[null, 'Semua', $total], ['menunggu', 'Menunggu pembayaran', $counts['menunggu'] ?? 0], ['dibayar', 'Dibayar', $counts['dibayar'] ?? 0], ['dikirim', 'Dikirim', $counts['dikirim'] ?? 0], ['selesai', 'Selesai', $counts['selesai'] ?? 0], ['dibatalkan', 'Dibatalkan', $counts['dibatalkan'] ?? 0]];
@endphp

<nav aria-label="Filter status pesanan" class="-mt-2 mb-6 flex gap-1 overflow-x-auto border-b border-ink/10">
    @foreach ($tabs as [$key, $label, $n])
        @php $on = $status === $key; @endphp
        <a href="{{ route('admin.orders', array_filter(['status' => $key])) }}" @if ($on) aria-current="page" @endif
           class="-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-bold {{ $on ? 'border-brand text-brand' : 'border-transparent text-ink/70 hover:text-ink' }}">{{ $label }} ({{ $n }})</a>
    @endforeach
</nav>

<div class="grid items-start gap-6 xl:grid-cols-[1fr_340px]">
    <section class="{{ $card }} overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[30rem] text-sm">
                <thead class="bg-mint"><tr><th class="{{ $th }}">No.</th><th class="{{ $th }}">Pemesan</th><th class="{{ $th }} text-right">Total</th><th class="{{ $th }}">Status</th></tr></thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse ($list as $o)
                        @php $sel = $selected && $selected['number'] === $o['number']; @endphp
                        <tr class="{{ $sel ? 'bg-mint' : 'hover:bg-mint/60' }}">
                            <td class="px-4 py-3 font-bold"><a href="{{ route('admin.orders', array_filter(['status' => $status, 'no' => $o['number']])) }}" @if ($sel) aria-current="true" @endif class="hover:underline">{{ $o['number'] }}</a></td>
                            <td class="px-4 py-3"><p class="font-bold">{{ $o['name'] }}</p><p class="text-xs text-ink/70">{{ $o['created'] }}</p></td>
                            <td class="px-4 py-3 text-right font-bold">{{ Fmt::rp($o['total']) }}</td>
                            <td class="px-4 py-3"><x-chip :type="$o['status']" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink/70">Belum ada pesanan dengan status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($selected)
        @php $s = $selected; @endphp
        <section class="{{ $card }} p-5" aria-labelledby="detail-title">
            <h2 id="detail-title" class="text-[22px] font-extrabold tracking-tight">Pesanan {{ $s['number'] }}</h2>
            <x-chip :type="$s['status']" class="mt-2" />

            <div class="mt-4 text-sm">
                <p class="font-bold">{{ $s['name'] }}</p>
                <p class="text-ink/80">{{ $s['phone'] }}</p>
                <p class="text-ink/80">{{ $s['address'] }}</p>
                <p class="mt-1 text-xs text-ink/70">Dibuat {{ $s['created'] }}</p>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-ink/10 text-sm">
                @foreach ($s['items'] as $i)
                    <p class="flex justify-between gap-3 border-b border-ink/10 px-3 py-2.5"><span>{{ $i['name'] }} × {{ $i['qty'] }}</span><span class="font-bold">{{ Fmt::rp($i['line']) }}</span></p>
                @endforeach
                <p class="flex justify-between bg-mint px-3 py-2.5 font-bold"><span>Total produk</span><span class="text-lg font-extrabold">{{ Fmt::rp($s['total']) }}</span></p>
            </div>

            @if ($s['status'] === 'menunggu')
                <p class="mt-4 text-sm text-ink/80">Bukti transfer: <strong>{{ $s['proof'] ? 'sudah dikirim lewat WhatsApp' : 'belum dikirim' }}</strong></p>
            @endif

            <form method="POST" action="{{ route('admin.orders.update', $s['number']) }}" class="mt-4 space-y-3">
                @csrf
                @if ($s['status'] === 'menunggu')
                    <p class="text-xs font-bold uppercase tracking-wider text-ink/60">Verifikasi pembayaran</p>
                    <x-button name="aksi" value="valid" class="w-full">Pembayaran valid</x-button>
                    <x-button variant="outline" name="aksi" value="tidak_valid" class="w-full">Bukti tidak valid</x-button>
                @elseif ($s['status'] === 'dibayar')
                    <x-button name="aksi" value="kirim" class="w-full">Tandai dikirim</x-button>
                @elseif ($s['status'] === 'dikirim')
                    <x-button name="aksi" value="selesai" class="w-full">Tandai selesai</x-button>
                @endif
                @if (in_array($s['status'], ['menunggu', 'dibayar']))
                    <x-button variant="danger" name="aksi" value="batal" class="w-full" :data-confirm="'Batalkan pesanan '.$s['number'].'?'">Batalkan pesanan</x-button>
                @endif
            </form>
            <x-button variant="wa" :href="'https://wa.me/'.\App\Support\Fmt::waNumber($s['phone'])" target="_blank" rel="noopener" class="mt-3 w-full"><x-icon name="chat" />Hubungi pelanggan lewat WhatsApp</x-button>
        </section>
    @endif
</div>
<p class="mt-4 text-sm text-ink/70">Status mengikuti siklus: Menunggu pembayaran → Dibayar → Dikirim → Selesai, atau Dibatalkan.</p>
@endsection
