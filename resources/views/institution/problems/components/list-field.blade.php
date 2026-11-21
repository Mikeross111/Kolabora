{{-- daftar isian dinamis (keterampilan, jurusan, luaran, fasilitas). nama input tidak berubah (mis. required_skills[]);
     baris awal dirender server (old() / data tersimpan), "+ Tambah" menyalin <template>. baris terakhir tidak dihapus, hanya dikosongkan. --}}
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $message = isset($errors) ? ($errors->first($key) ?: $errors->first($key.'.*')) : null;
    $values = array_values(array_filter((array) $values, fn ($v) => ! is_array($v)));
    if (count($values) === 0) {
        $values = [''];
    }
    $required = $required ?? false;
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
@endphp
<div class="kb-field" x-data="kbListField">
    <span class="kb-label" id="{{ $id }}-label" @if ($required) data-required @endif>{{ $label }}</span>
    @if ($help)
        <p class="kb-help" id="{{ $id }}-help">{{ $help }}</p>
    @endif
    <ul class="kb-inst-list" id="{{ $id }}" x-ref="list" aria-labelledby="{{ $id }}-label">
        @foreach ($values as $value)
            <li class="kb-inst-list__row">
                <input type="text" name="{{ $name }}" value="{{ $value }}" class="kb-input" aria-labelledby="{{ $id }}-label"
                       @if ($help) aria-describedby="{{ $id }}-help" @endif
                       @if ($required) required @endif @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                       @if ($message) aria-invalid="true" data-touched @endif>
                <button type="button" class="kb-icon-btn" @click="remove($event)" aria-label="Hapus isian {{ \Illuminate\Support\Str::lower($label) }}"><x-icon name="x" size="sm" /></button>
            </li>
        @endforeach
    </ul>
    <template x-ref="row">
        <li class="kb-inst-list__row">
            <input type="text" name="{{ $name }}" value="" class="kb-input" aria-labelledby="{{ $id }}-label"
                   @if ($help) aria-describedby="{{ $id }}-help" @endif
                   @if ($required) required @endif @if ($placeholder) placeholder="{{ $placeholder }}" @endif>
            <button type="button" class="kb-icon-btn" @click="remove($event)" aria-label="Hapus isian {{ \Illuminate\Support\Str::lower($label) }}"><x-icon name="x" size="sm" /></button>
        </li>
    </template>
    <div>
        <button type="button" class="kb-btn kb-btn--tertiary" x-ref="add" @click="add()"><x-icon name="plus" size="sm" />{{ $addLabel }}</button>
    </div>
    @if ($message)
        <p class="kb-error" id="{{ $id }}-error"><x-icon name="circle-alert" size="sm" />{{ $message }}</p>
    @endif
</div>
