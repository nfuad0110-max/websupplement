@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])
@php
    // Tinggi sentuh 44 px. Oranye hanya untuk satu aksi utama per layar, selalu dengan teks gelap.
    $base = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold transition-colors disabled:cursor-not-allowed';
    $v = [
        'primary' => 'bg-brand text-white hover:bg-brand-dark',
        'accent'  => 'bg-accent text-ink hover:bg-[#E07D17]',
        'outline' => 'border border-ink/20 bg-white text-ink hover:bg-mint disabled:border-ink/10 disabled:bg-white disabled:text-ink/50',
        'danger'  => 'border border-red-300 bg-white text-red-700 hover:bg-red-50',
        'wa'      => 'bg-wa text-white hover:bg-[#126F37]',
    ][$variant];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $v"]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base $v"]) }}>{{ $slot }}</button>
@endif
