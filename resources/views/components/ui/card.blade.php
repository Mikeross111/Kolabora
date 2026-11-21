{{-- kartu rekaman: HANYA untuk tampilan galeri opsional (default tabel/daftar, guardrail 3).
     image hanya bila ada gambar nyata (tanpa placeholder, guardrail 7); bila gagal dimuat, area media disembunyikan. --}}
@props(['title', 'href' => null, 'image' => null, 'imageAlt' => '', 'headingLevel' => 3])
<article {{ $attributes->class('kb-card') }}>
    @if ($image)
        <div class="kb-card__media kb-media">
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" data-optional onerror="this.closest('.kb-media').hidden = true">
        </div>
    @endif
    <div class="kb-card__body">
        <h{{ $headingLevel }} class="kb-card__title">
            @if ($href)<a href="{{ $href }}">{{ $title }}</a>@else{{ $title }}@endif
        </h{{ $headingLevel }}>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="kb-card__foot">{{ $footer }}</div>
    @endisset
</article>
