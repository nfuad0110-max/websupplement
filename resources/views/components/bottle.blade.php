@props(['color' => '#4C9F3A'])
{{-- Ilustrasi botol sesuai desain. Ganti dengan <img> bila foto produk sudah ada. --}}
<svg viewBox="0 0 80 120" {{ $attributes->merge(['class' => 'h-24 w-auto']) }} aria-hidden="true">
    <rect x="24" y="4" width="32" height="14" rx="3" fill="#1B5E3F"/>
    <rect x="8" y="16" width="64" height="100" rx="18" fill="{{ $color }}"/>
    <rect x="18" y="46" width="44" height="36" rx="5" fill="#FFFFFF"/>
    <rect x="24" y="53" width="32" height="5" rx="2.5" fill="{{ $color }}"/>
    <rect x="24" y="64" width="22" height="3" rx="1.5" fill="#CFD8D2"/>
    <rect x="24" y="71" width="26" height="3" rx="1.5" fill="#CFD8D2"/>
</svg>
