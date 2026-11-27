@extends('layouts.shop')
@use('App\Support\Fmt')
@section('title', 'Keranjang belanja')
@section('steps')<x-steps :current="1" />@endsection

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Keranjang belanja</h1>

@if (count($items) === 0)
    <div class="mt-6 rounded-2xl border border-ink/10 bg-white p-10 text-center">
        <p class="font-bold">Keranjang masih kosong.</p>
        <p class="mt-1 text-sm text-ink/70">Pilih produk dari katalog, lalu kembali ke sini untuk mengisi data pemesan.</p>
        <x-button :href="route('shop.catalog')" class="mt-4">Lihat katalog</x-button>
    </div>
@else
    <form method="POST" action="{{ route('shop.order.store') }}" data-totals class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
        @csrf
        <div class="space-y-6">
            {{-- Daftar produk --}}
            <div class="overflow-hidden rounded-2xl border border-ink/10 bg-white">
                <div class="hidden grid-cols-[1fr_140px_110px_32px] gap-4 bg-mint px-4 py-3 text-xs font-bold uppercase tracking-wider text-ink/60 sm:grid">
                    <span>Produk</span><span>Jumlah</span><span class="text-right">Subtotal</span><span></span>
                </div>
                <ul class="divide-y divide-ink/10">
                    @foreach ($items as $item)
                        @php $p = $item['product']; @endphp
                        <li class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-3 px-4 py-4 sm:grid-cols-[1fr_140px_110px_32px]">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('shop.product', $p['slug']) }}" class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-mint" aria-hidden="true" tabindex="-1">
                                    @if (! empty($p['image_url']))
                                        <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover">
                                    @else
                                        <x-bottle :color="$p['color']" class="h-10" />
                                    @endif
                                </a>
                                <div class="min-w-0">
                                    <a href="{{ route('shop.product', $p['slug']) }}" class="font-bold hover:text-brand">{{ $p['name'] }}</a>
                                    <p class="text-sm text-ink/70">{{ Fmt::rp($p['price']) }} per botol</p>
                                    <p class="text-xs text-ink/70">Stok tersedia {{ $p['stock'] }}</p>
                                </div>
                            </div>
                            <button type="submit" form="remove-{{ $p['id'] }}" aria-label="Hapus {{ $p['name'] }}" class="grid h-11 w-11 place-items-center justify-self-end rounded-lg text-red-700 hover:bg-red-50 sm:order-last">
                                <x-icon name="close" />
                            </button>
                            <x-qty :name="'qty['.$p['id'].']'" :value="$item['qty']" :max="min(99, $p['stock'])" :price="$p['price']" :data-id="$p['id']" />
                            <p class="text-right font-bold" data-line="{{ $p['id'] }}">{{ Fmt::rp($item['line']) }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Data pemesan (tanpa login) --}}
            <section class="rounded-2xl border border-ink/10 bg-white p-5 sm:p-6">
                <h2 class="text-[22px] font-extrabold tracking-tight">Data pemesan</h2>
                <p class="text-sm text-ink/70">Tanpa login. Admin memakai data ini untuk menghubungi dan mengirim pesanan.</p>
                <div class="mt-5 space-y-4">
                    <x-input label="Nama lengkap" name="nama" autocomplete="name" required />
                    <x-input label="Nomor WhatsApp" name="whatsapp" type="tel" inputmode="tel" autocomplete="tel" placeholder="0812-3456-7801" required />
                    <x-input label="Alamat pengiriman" name="alamat" :textarea="true" autocomplete="street-address" required />
                    <x-input label="Catatan untuk penjual (opsional)" name="catatan" placeholder="Contoh: kirim sore hari" />
                </div>
            </section>
        </div>

        {{-- Ringkasan --}}
        <aside class="rounded-2xl border border-ink/10 bg-white p-5 lg:sticky lg:top-20">
            <h2 class="text-[22px] font-extrabold tracking-tight">Ringkasan</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink/70">Subtotal (<span data-count>{{ array_sum(array_column($items, 'qty')) }}</span> barang)</dt><dd class="font-bold" data-sum>{{ Fmt::rp(array_sum(array_column($items, 'line'))) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink/70">Ongkos kirim</dt><dd class="max-w-[10rem] text-right">Dikonfirmasi admin lewat WhatsApp</dd></div>
            </dl>
            <div class="mt-4 flex items-baseline justify-between border-t border-ink/10 pt-4">
                <span class="font-bold">Total produk</span>
                <span class="text-2xl font-extrabold" data-sum>{{ Fmt::rp(array_sum(array_column($items, 'line'))) }}</span>
            </div>
            <x-button variant="accent" class="mt-5 w-full">Buat pesanan</x-button>
            <p class="mt-3 text-sm text-ink/70">Setelah pesanan dibuat, Anda mendapat nomor rekening dan tautan WhatsApp untuk mengirim bukti transfer.</p>
        </aside>
    </form>

    {{-- Form hapus ditaruh di luar form utama (form tidak boleh bersarang) --}}
    @foreach ($items as $item)
        <form id="remove-{{ $item['product']['id'] }}" method="POST" action="{{ route('shop.cart.remove', $item['product']['id']) }}" class="hidden">@csrf @method('DELETE')</form>
    @endforeach
@endif
</div>
@endsection
