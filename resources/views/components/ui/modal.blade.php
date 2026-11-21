{{-- dialog modal (native <dialog>): fokus masuk ke dialog, esc menutup, latar inert, fokus kembali ke pemicu.
     buka: <button data-kb-open="{id}"> atau $dispatch('kb-open-modal', '{id}'); tutup: data-kb-close, esc, klik latar.
     drawer = panel samping. slot footer = tombol aksi. --}}
@props(['id', 'title', 'drawer' => false])
<dialog id="{{ $id }}" {{ $attributes->class(['kb-dialog', 'kb-drawer' => $drawer]) }} aria-labelledby="{{ $id }}-title">
    <div class="kb-dialog__head">
        <h2 id="{{ $id }}-title">{{ $title }}</h2>
        <button type="button" class="kb-icon-btn" data-kb-close aria-label="Tutup"><x-icon name="x" /></button>
    </div>
    <div class="kb-dialog__body">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="kb-dialog__foot">{{ $footer }}</div>
    @endisset
</dialog>
