@props(['current' => 1])
<ol class="flex items-center gap-1.5 text-xs sm:text-sm" aria-label="Langkah pemesanan">
    @foreach (['Keranjang', 'Bayar', 'Kirim bukti'] as $i => $label)
        <li class="{{ $i + 1 === $current ? 'font-bold text-brand' : 'text-ink/60' }}" @if ($i + 1 === $current) aria-current="step" @endif>{{ $i + 1 }} {{ $label }}</li>
        @unless ($loop->last)<li aria-hidden="true" class="text-ink/40">›</li>@endunless
    @endforeach
</ol>
