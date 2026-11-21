{{-- unggah berkas: dropzone (label di sekitar input file asli) + daftar berkas terpilih (nama, ukuran, hapus).
     input file asli yang dikirim: name/id/accept/multiple tidak berubah; enctype="multipart/form-data" tetap di <form> pemanggil.
     hint = jenis & ukuran ("PDF, maks. 5 MB"). max-size (byte) = peringatan di peramban saja; validasi tetap di server.
     current = berkas tersimpan ['name' => …, 'url' => …] (ditampilkan sebagai tautan). --}}
@props([
    'name',
    'id' => null,
    'label',
    'accept' => null,
    'multiple' => false,
    'required' => false,
    'hint' => null,
    'maxSize' => null,
    'current' => null,
])
@php
    $id = $id ?? str_replace(['[]', '[', ']'], ['', '-', ''], $name);
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $message = isset($errors) ? ($errors->first($key) ?: $errors->first($key.'.*')) : null;
    $describedBy = trim(($hint ? $id.'-help ' : '').($message ? $id.'-error' : ''));
@endphp
<div {{ $attributes->class('kb-field') }}
     x-data="kbUpload({ multiple: @js((bool) $multiple), accept: @js($accept), maxSize: @js($maxSize) })">
    <span class="kb-label" id="{{ $id }}-label" @if ($required) data-required @endif>{{ $label }}</span>

    <label class="kb-dropzone" for="{{ $id }}" @if ($message) data-invalid @endif
           :data-dragover="drag ? '' : null" :data-invalid="error || @js((bool) $message) ? '' : null"
           @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="onDrop($event)">
        <x-icon name="upload" size="lg" />
        <span><strong>Pilih berkas</strong> atau tarik ke sini</span>
        @if ($hint)
            <span class="kb-help" id="{{ $id }}-help">{{ $hint }}</span>
        @endif
        <input type="file" class="sr-only" name="{{ $name }}" id="{{ $id }}" x-ref="input" @change="sync()"
               aria-labelledby="{{ $id }}-label"
               @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
               @if ($accept) accept="{{ $accept }}" @endif
               @if ($multiple) multiple @endif
               @if ($required) required @endif
               @if ($message) aria-invalid="true" @endif>
    </label>

    @if ($current && ! empty($current['name']))
        <p class="kb-help">Berkas saat ini:
            @if (! empty($current['url']))
                <a href="{{ $current['url'] }}" class="text-brand-700 underline" target="_blank" rel="noopener">{{ $current['name'] }}</a>
            @else
                {{ $current['name'] }}
            @endif
        </p>
    @endif

    <ul class="kb-file-list" x-show="files.length > 0" x-cloak aria-label="Berkas terpilih">
        <template x-for="(file, index) in files" :key="file.name + index">
            <li class="kb-file-chip">
                <x-icon name="file-text" size="sm" />
                <span class="kb-file-chip__name" x-text="file.name" :title="file.name"></span>
                <span class="kb-file-chip__meta" x-text="file.size"></span>
                <button type="button" class="kb-icon-btn" @click="remove(index)" :aria-label="'Hapus ' + file.name"><x-icon name="x" size="sm" /></button>
            </li>
        </template>
    </ul>

    <p class="kb-error" x-show="error" x-cloak role="alert"><x-icon name="circle-alert" size="sm" /><span x-text="error"></span></p>
    @if ($message)
        <p class="kb-error" id="{{ $id }}-error"><x-icon name="circle-alert" size="sm" />{{ $message }}</p>
    @endif
</div>
