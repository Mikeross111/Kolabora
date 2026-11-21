{{-- tombol. variant: primary (satu per tampilan) | secondary | tertiary | danger (outline) | danger-solid (hanya di dialog konfirmasi).
     href -> <a>; tanpa href -> <button type>. icon/icon-end: nama ikon lucide. loading: spinner + nonaktif + aria-busy. --}}
@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconEnd' => null,
    'loading' => false,
    'disabled' => false,
    'block' => false,
])
@php
    $variant = in_array($variant, ['primary', 'secondary', 'tertiary', 'danger', 'danger-solid'], true) ? $variant : 'primary';
    $classes = ['kb-btn', 'kb-btn--'.$variant, 'kb-btn--lg' => $size === 'lg', 'kb-btn--block' => $block];
@endphp
@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" size="sm" />@endif
        {{ $slot }}
        @if ($iconEnd)<x-icon :name="$iconEnd" size="sm" />@endif
    </a>
@elseif ($href)
    <a role="link" aria-disabled="true" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} @disabled($disabled || $loading) @if ($loading) data-loading aria-busy="true" @endif>
        @if ($loading)
            <span class="kb-spinner" aria-hidden="true"></span>
        @elseif ($icon)
            <x-icon :name="$icon" size="sm" />
        @endif
        {{ $slot }}
        @if ($iconEnd && ! $loading)<x-icon :name="$iconEnd" size="sm" />@endif
    </button>
@endif
