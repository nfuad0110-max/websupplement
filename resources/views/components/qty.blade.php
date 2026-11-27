@props(['name' => 'qty', 'value' => 1, 'min' => 1, 'max' => 99, 'price' => null])
<div data-stepper class="inline-flex h-11 items-center rounded-xl border border-ink/20 bg-white">
    <button type="button" data-step="-1" aria-label="Kurangi jumlah" class="h-full w-10 rounded-l-xl text-lg font-bold hover:bg-mint">&minus;</button>
    <input type="number" name="{{ $name }}" value="{{ $value }}" min="{{ $min }}" max="{{ $max }}" inputmode="numeric" aria-label="Jumlah"
           @if ($price) data-price="{{ $price }}" @endif
           {{ $attributes->merge(['class' => 'h-full w-12 border-0 bg-transparent p-0 text-center font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none']) }}>
    <button type="button" data-step="1" aria-label="Tambah jumlah" class="h-full w-10 rounded-r-xl text-lg font-bold hover:bg-mint">+</button>
</div>
