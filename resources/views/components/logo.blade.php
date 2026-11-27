@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-extrabold tracking-tight']) }}>
    <span class="grid h-9 w-9 place-items-center rounded-xl {{ $dark ? 'bg-white' : 'bg-brand' }}">
        <svg viewBox="0 0 24 24" class="h-6 w-6" aria-hidden="true">
            <g transform="rotate(-45 12 12)">
                <path d="M12 7H8.5a5 5 0 0 0 0 10H12z" fill="#F08A24"/>
                <path d="M12 7h3.5a5 5 0 0 1 0 10H12z" fill="{{ $dark ? '#1B5E3F' : '#FFFFFF' }}"/>
            </g>
        </svg>
    </span>
    <span class="text-xl {{ $dark ? 'text-white' : 'text-brand' }}">SUPPLE<span class="{{ $dark ? 'text-accent' : 'text-[#C2650A]' }}">MART</span></span>
</span>
