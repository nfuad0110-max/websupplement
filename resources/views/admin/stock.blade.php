@extends('layouts.admin')
@use('App\Support\Fmt')
@section('eyebrow', 'Dari supplier ke gudang')
@section('title', 'Stok masuk')

@section('content')
@php
    $card = 'rounded-2xl border border-ink/10 bg-white';
    $th = 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-ink/60';
    $field = 'mt-1 block w-full rounded-xl border border-ink/20 bg-white px-4 py-3 text-[15px]';
@endphp
<div class="grid items-start gap-6 xl:grid-cols-[1fr_340px]">
    <section class="{{ $card }} overflow-hidden">
        <div class="p-5">
            <h2 class="text-[22px] font-extrabold tracking-tight">Batch stok</h2>
            <p class="text-sm text-ink/70">Diurutkan dari kedaluwarsa terdekat. Jual batch paling atas lebih dulu.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-sm">
                <thead class="bg-mint"><tr><th class="{{ $th }}">Batch</th><th class="{{ $th }}">Produk</th><th class="{{ $th }}">Masuk</th><th class="{{ $th }}">Sisa</th><th class="{{ $th }}">Kedaluwarsa</th><th class="{{ $th }}">Status</th></tr></thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse ($batches as $b)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $b['code'] }}</td>
                            <td class="px-4 py-3"><p class="font-bold">{{ $b['product']['name'] }}</p><p class="text-xs text-ink/70">Diterima {{ Fmt::date($b['received']) }}</p></td>
                            <td class="px-4 py-3">{{ $b['in'] }}</td>
                            <td class="px-4 py-3 font-bold">{{ $b['left'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ Fmt::date($b['expiry']) }}</td>
                            <td class="px-4 py-3"><x-chip :type="$b['state']['key']">{{ $b['state']['text'] }}</x-chip></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-ink/70">Belum ada batch stok tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="{{ $card }} p-5">
        <h2 class="text-[22px] font-extrabold tracking-tight">Catat stok masuk</h2>
        <form method="POST" action="{{ route('admin.stock.store') }}" class="mt-4 space-y-4">
            @csrf
            <fieldset>
                <legend class="text-sm font-bold">Produk</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($products as $p)
                        <x-choice name="product_id" :value="$p['id']" :label="$p['name']" :checked="(int) old('product_id') === (int) $p['id']" />
                    @endforeach
                </div>
                @error('product_id')<p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
            </fieldset>
            <x-input label="Jumlah masuk (unit)" name="quantity" type="number" inputmode="numeric" min="1" required />
            <x-input label="Tanggal masuk" name="received_at" type="date" :value="now()->toDateString()" required />
            <x-input label="Tanggal kedaluwarsa" name="expires_at" type="date" required />
            <x-button class="w-full">Simpan stok masuk</x-button>
            <p class="text-sm text-ink/70">Stok produk bertambah sebesar jumlah masuk. Pencatat: {{ auth()->user()?->name ?? 'Admin Toko' }}.</p>
        </form>
    </section>
</div>
<p class="mt-4 text-sm text-ink/70">Hari ini: {{ Fmt::dateLong(now()) }}.</p>
@endsection
