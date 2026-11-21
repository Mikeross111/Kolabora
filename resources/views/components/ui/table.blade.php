{{-- tabel rekaman. slot head = isi <tr> header (<th scope="col">…); slot utama = baris <tr> (tiap <td data-label="…"> untuk tampilan bertumpuk).
     stack = di bawah 640px baris ditumpuk (tidak pernah terpotong); tanpa stack, wadah boleh digulir horizontal (satu wadah).
     caption wajib untuk pembaca layar (tersembunyi bila caption-visible=false). --}}
@props(['caption', 'stack' => true, 'captionVisible' => false])
<div {{ $attributes->class('kb-table-wrap') }} role="region" aria-label="{{ $caption }}" tabindex="0">
    <table @class(['kb-table', 'kb-table--stack' => $stack])>
        <caption @class(['sr-only' => ! $captionVisible, 'kb-table__caption' => $captionVisible])>{{ $caption }}</caption>
        @isset($head)
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
