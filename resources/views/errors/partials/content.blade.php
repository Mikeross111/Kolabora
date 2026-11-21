{{-- phase 26: isi halaman galat yang sama untuk semua kode (dipakai layouts.app dan errors.standalone).
     tanpa query, tanpa session, tanpa data pengguna: hanya request header + route(). jangan tampilkan detail teknis. --}}
@php
    // "kembali": referer dari host yang sama (bukan session, agar aman saat database mati), selain itu ke beranda
    $kbErrorBack = request()->headers->get('referer');
    if (! $kbErrorBack || parse_url($kbErrorBack, PHP_URL_HOST) !== request()->getHost() || $kbErrorBack === request()->fullUrl()) {
        $kbErrorBack = route('home');
    }
@endphp
<section class="kb-error" aria-labelledby="kb-error-title">
    <div class="kb-error__panel">
        <p class="kb-error__code"><span class="sr-only">Kode galat </span>{{ $code }}</p>
        <h1 id="kb-error-title" class="kb-error__title">{{ $title }}</h1>
        <p class="kb-error__desc">{{ $description }}</p>

        <div class="kb-error__actions">
            {{-- riwayat browser lebih dulu; tanpa javascript tautan tetap menuju referer atau beranda --}}
            <a href="{{ $kbErrorBack }}" class="kb-btn kb-btn--secondary" onclick="if (window.history.length > 1) { window.history.back(); return false; }">
                <x-icon name="arrow-left" size="sm" />
                Kembali
            </a>
            <a href="{{ route('home') }}" class="kb-btn kb-btn--primary">
                <x-icon name="house" size="sm" />
                Ke beranda
            </a>
        </div>

        <p class="kb-error__help">
            Butuh bantuan? <a href="{{ route('contact') }}">Hubungi tim Kolabora</a>.
        </p>
    </div>
</section>
