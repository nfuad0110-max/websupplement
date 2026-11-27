@props(['type' => 'tersedia'])
@php
    // Status selalu ditulis dengan kata dan titik warna, tidak hanya warna.
    $map = [
        'menunggu'    => ['Menunggu pembayaran', 'bg-amber-100 text-amber-900', 'bg-amber-600'],
        'dibayar'     => ['Dibayar', 'bg-blue-100 text-blue-900', 'bg-blue-600'],
        'dikirim'     => ['Dikirim', 'bg-teal-100 text-teal-900', 'bg-teal-700'],
        'selesai'     => ['Selesai', 'bg-emerald-100 text-emerald-900', 'bg-emerald-700'],
        'dibatalkan'  => ['Dibatalkan', 'bg-red-100 text-red-800', 'bg-red-600'],
        'tersedia'    => ['Tersedia', 'bg-emerald-100 text-emerald-900', 'bg-emerald-700'],
        'menipis'     => ['Stok menipis', 'bg-amber-100 text-amber-900', 'bg-amber-600'],
        'habis'       => ['Stok habis', 'bg-red-100 text-red-800', 'bg-red-600'],
        'hampir'      => ['Hampir kedaluwarsa', 'bg-amber-100 text-amber-900', 'bg-amber-600'],
        'segera'      => ['Segera kedaluwarsa', 'bg-red-100 text-red-800', 'bg-red-600'],
        'kedaluwarsa' => ['Kedaluwarsa', 'bg-red-100 text-red-800', 'bg-red-600'],
        'aman'        => ['Aman', 'bg-emerald-100 text-emerald-900', 'bg-emerald-700'],
        'terjual'     => ['Terjual habis', 'bg-stone-200 text-stone-700', 'bg-stone-500'],
        'netral'      => ['', 'bg-stone-200 text-stone-700', 'bg-stone-500'],
    ];
    $c = $map[$type] ?? $map['tersedia'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {$c[1]}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $c[2] }}" aria-hidden="true"></span>{{ $slot->isEmpty() ? $c[0] : $slot }}
</span>
