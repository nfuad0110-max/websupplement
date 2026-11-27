@extends('layouts.admin')
@use('App\Support\Fmt')
@section('eyebrow', $report['label'])
@section('title', 'Rekap penjualan')
@section('actions')
    <x-button variant="outline" :href="route('owner.export', ['format' => 'pdf', 'periode' => $active])"><x-icon name="download" class="h-4 w-4" />Unduh PDF</x-button>
    <x-button variant="outline" :href="route('owner.export', ['format' => 'excel', 'periode' => $active])"><x-icon name="download" class="h-4 w-4" />Unduh Excel</x-button>
@endsection

@section('content')
@php
    $card = 'rounded-2xl border border-ink/10 bg-white';
    $th = 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-ink/60';
    $total = $report['total_sales'] ?? array_sum(array_column($report['top'], 2));
    $avg = $report['orders'] > 0 ? intdiv((int) $total, (int) $report['orders']) : 0;
    $maxVal = max(array_column($report['daily'], 1) ?: [0]);
    $maxScale = max(1000, (int) (ceil($maxVal / 500) * 500));
    $midScale = intdiv($maxScale, 2);
@endphp

<div class="flex flex-wrap gap-2" role="group" aria-label="Periode">
    @foreach ($periods as $p)
        <a href="{{ route('owner.report', ['periode' => $p]) }}" @if ($p === $active) aria-current="true" @endif
           class="inline-flex min-h-11 items-center rounded-full border px-5 text-sm font-semibold {{ $p === $active ? 'border-brand-dark bg-brand-dark text-white' : 'border-ink/20 bg-white hover:bg-mint' }}">{{ $p }}</a>
    @endforeach
</div>

<div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ([['Total penjualan', Fmt::rp($total)], ['Pesanan terhitung', $report['orders']], ['Unit terjual', $report['units']], ['Rata-rata per pesanan', Fmt::rp($avg)]] as [$label, $val])
        <div class="{{ $card }} p-5"><p class="text-sm text-ink/70">{{ $label }}</p><p class="mt-2 whitespace-nowrap text-2xl font-extrabold sm:text-3xl">{{ $val }}</p></div>
    @endforeach
</div>

{{-- Grafik batang --}}
<section class="{{ $card }} mt-6 p-5">
    <h2 class="text-[22px] font-extrabold tracking-tight">Penjualan per hari</h2>
    <p class="text-sm text-ink/70">Dalam ribuan rupiah</p>
    <figure class="mt-6">
        <div class="relative h-64 pl-10">
            <div class="absolute inset-y-0 left-10 right-0 flex flex-col justify-between" aria-hidden="true">
                @foreach ([$maxScale, $midScale, 0] as $t)
                    <div class="relative border-t border-ink/10"><span class="absolute -left-10 -top-2.5 w-8 text-right text-xs text-ink/60">{{ $t }}</span></div>
                @endforeach
            </div>
            <div class="absolute inset-y-0 left-10 right-0 flex items-end gap-2 px-2 sm:gap-4 sm:px-4">
                @foreach ($report['daily'] as [$day, $v])
                    @php $pctH = $maxScale > 0 ? min(100, ($v / $maxScale) * 100) : 0; @endphp
                    <div class="flex h-full flex-1 flex-col items-center justify-end">
                        <span class="mb-1 text-xs font-bold">{{ $v ?: '–' }}</span>
                        <div class="w-full max-w-14 rounded-t-lg bg-brand" style="height: max(2px, {{ $pctH }}%)"></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="flex gap-2 pl-10 pt-2 sm:gap-4">
            @foreach ($report['daily'] as [$day, $v])<span class="flex-1 text-center text-xs text-ink/70">{{ $day }}</span>@endforeach
        </div>
        <figcaption class="sr-only">Penjualan harian dalam ribuan rupiah, {{ $report['label'] }}</figcaption>
    </figure>
</section>

<section class="{{ $card }} mt-6 overflow-hidden">
    <div class="p-5">
        <h2 class="text-[22px] font-extrabold tracking-tight">Produk terlaris</h2>
        <p class="text-sm text-ink/70">Berdasarkan nilai penjualan pada periode ini.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[34rem] text-sm">
            <thead class="bg-mint"><tr><th class="{{ $th }}">Produk</th><th class="{{ $th }} text-right">Unit</th><th class="{{ $th }} text-right">Penjualan</th><th class="{{ $th }}">Porsi</th></tr></thead>
            <tbody class="divide-y divide-ink/10">
                @forelse ($report['top'] as [$name, $units, $sales])
                    @php $pct = $total > 0 ? round($sales / $total * 100) : 0; @endphp
                    <tr>
                        <td class="px-4 py-3 font-bold">{{ $name }}</td>
                        <td class="px-4 py-3 text-right">{{ $units }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ Fmt::rp($sales) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-28 overflow-hidden rounded-full bg-emerald-100" role="presentation"><div class="h-full rounded-full bg-brand" style="width: {{ $pct }}%"></div></div>
                                <span class="w-9 text-right text-xs text-ink/80">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-ink/70">Belum ada penjualan terhitung pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<p class="mt-4 text-sm text-ink/70">Pesanan yang dibatalkan atau masih menunggu pembayaran tidak dihitung.</p>
@endsection
