@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'h-5 w-5']) }} aria-hidden="true">
    @switch($name)
        @case('dashboard') <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/> @break
        @case('box') <path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/> @break
        @case('inbox') <path d="M3 13h5l1 3h6l1-3h5"/><path d="M5 5h14l2 8v6H3v-6z"/> @break
        @case('receipt') <path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/> @break
        @case('chart') <path d="M5 20V11M11 20V5M17 20v-6M3 20h18"/> @break
        @case('cart') <path d="M2.5 3h1.9l.5 2m0 0 1.7 8.2a1 1 0 0 0 1 .8h9.4a1 1 0 0 0 1-.76L19 7a1 1 0 0 0-1-1.25z"/><circle cx="9" cy="19.5" r="1.2"/><circle cx="17" cy="19.5" r="1.2"/> @break
        @case('search') <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/> @break
        @case('download') <path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14"/> @break
        @case('close') <path d="M6 6l12 12M18 6 6 18"/> @break
        @case('chat') <path d="M20 12a8 8 0 0 1-11.8 7L4 20l1-4A8 8 0 1 1 20 12z"/> @break
    @endswitch
</svg>
