@props(['product'])
@use('App\Models\Produk')
@use('App\Support\Fmt')
@php $st = Produk::stockStatus($product['stock'], $product['min_stock'] ?? null); @endphp
<article class="flex flex-col rounded-2xl border border-ink/10 bg-white p-3">
    <a href="{{ route('shop.product', $product['slug']) }}" tabindex="-1" aria-hidden="true" class="grid aspect-square place-items-center overflow-hidden rounded-xl bg-mint">
        @if (! empty($product['image_url']))
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
        @else
            <x-bottle :color="$product['color']" class="h-24 sm:h-28" />
        @endif
    </a>
    <div class="flex flex-1 flex-col pt-3">
        <p class="text-xs font-bold uppercase tracking-wider text-ink/60">{{ $product['category'] }}</p>
        <h3 class="mt-0.5 text-base font-bold leading-snug sm:text-[17px]">
            <a href="{{ route('shop.product', $product['slug']) }}" class="hover:text-brand">{{ $product['name'] }}</a>
        </h3>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-x-2 gap-y-1.5">
            <p class="font-bold sm:text-[17px]">{{ Fmt::rp($product['price']) }}</p>
            <x-chip :type="$st['key']">{{ $st['text'] }}</x-chip>
        </div>
        <p class="mt-1.5 text-xs text-ink/70">
            @if (! empty($product['expiry']))
                Kedaluwarsa {{ Fmt::monthYear($product['expiry']) }}
            @else
                Belum ada batch aktif
            @endif
        </p>
        <div class="mt-auto pt-3">
            @if ($product['stock'] < 1)
                <x-button variant="outline" type="button" disabled class="w-full">Stok habis</x-button>
            @else
                <form method="POST" action="{{ route('shop.cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                    <x-button class="w-full">
                        <span class="sm:hidden">Tambah</span><span class="hidden sm:inline">Tambah ke keranjang</span>
                    </x-button>
                </form>
            @endif
        </div>
    </div>
</article>
