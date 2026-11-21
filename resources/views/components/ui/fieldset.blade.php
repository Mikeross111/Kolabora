{{-- kelompok radio/checkbox dengan legend; galat $errors[name] ditampilkan di bawah --}}
@props(['legend', 'name' => null, 'required' => false, 'help' => null])
@php
    $key = $name ? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;
    $message = ($key && isset($errors)) ? $errors->first($key) : null;
@endphp
<fieldset {{ $attributes->class('kb-fieldset') }} @if ($message) aria-invalid="true" @endif>
    <legend @if ($required) class="kb-label" data-required @endif>{{ $legend }}</legend>
    {{ $slot }}
    @if ($help)<p class="kb-help">{{ $help }}</p>@endif
    @if ($message)<p class="kb-error"><x-icon name="circle-alert" size="sm" />{{ $message }}</p>@endif
</fieldset>
