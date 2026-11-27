@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'textarea' => false, 'placeholder' => null, 'required' => false])
@php
    $bad = $errors->has($name);
    $cls = 'mt-1 block w-full rounded-xl border bg-white px-4 py-3 text-[15px] placeholder:text-ink/50 ' . ($bad ? 'border-red-600' : 'border-ink/20');
@endphp
<div>
    <label for="{{ $name }}" class="text-sm font-bold">{{ $label }}</label>
    @if ($hint)<p id="{{ $name }}-hint" class="text-sm text-ink/70">{{ $hint }}</p>@endif
    @if ($textarea)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" placeholder="{{ $placeholder }}" @required($required) @if ($bad) aria-invalid="true" @endif class="{{ $cls }}">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" @required($required) @if ($bad) aria-invalid="true" @endif {{ $attributes->merge(['class' => $cls]) }}>
    @endif
    @error($name)<p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
</div>
