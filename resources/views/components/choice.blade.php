@props(['name', 'value', 'label', 'checked' => false, 'shape' => 'rounded-full'])
<label class="block cursor-pointer">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked) class="peer sr-only">
    <span class="inline-flex min-h-11 items-center border border-ink/20 bg-white px-4 text-sm font-semibold {{ $shape }} peer-checked:border-brand-dark peer-checked:bg-brand-dark peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand {{ $attributes->get('class') }}">{{ $label }}</span>
</label>
