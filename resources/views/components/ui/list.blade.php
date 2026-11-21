{{-- daftar rekaman bergaris pemisah (orang, notifikasi, dokumen). isi dengan x-ui.list-item atau <li> sendiri. --}}
@props(['label' => null])
<ul {{ $attributes->class('kb-list') }} @if ($label) aria-label="{{ $label }}" @endif>
    {{ $slot }}
</ul>
