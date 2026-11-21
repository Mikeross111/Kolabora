@extends('layouts.app')

@section('title', 'Kontak')

{{-- phase 5: kontak satu layar (J35): kepala polos tanpa hero foto (J6), kanal email resmi dari pemilik
     produk (2026-10-31), FAQ dari fakta produk (DRAFT, D-23 b). tidak ada formulir pesan: fitur baru
     tanpa backend (D-26). foto tim dipindah ke halaman tentang kami. --}}

@php
    // pertanyaan umum: setiap jawaban mengacu ke fungsi yang ada di aplikasi
    $faqs = [
        [
            'q' => 'Siapa yang bisa mendaftar di Kolabora?',
            'a' => 'Mahasiswa, instansi, dan perusahaan. Setiap akun memilih satu peran saat mendaftar, dan peran itu menentukan ruang kerjanya.',
        ],
        [
            'q' => 'Bagaimana instansi mempublikasikan masalah KKN?',
            'a' => 'Daftar sebagai instansi dengan dokumen verifikasi, lalu tulis masalah dari menu Masalah: lokasi, SDG, kuota mahasiswa, tenggat, dan persyaratannya.',
        ],
        [
            'q' => 'Bagaimana mahasiswa melamar?',
            'a' => 'Buka detail masalah, tulis motivasi minimal 100 karakter, dan lampirkan berkas yang diminta. Status lamaran dapat dipantau di halaman Aplikasi Saya.',
        ],
        [
            'q' => 'Apakah masalah bisa dilihat tanpa akun?',
            'a' => 'Profil publik instansi dan daftar masalahnya bisa dibuka siapa saja. Detail masalah dan lamaran tersedia setelah masuk sebagai mahasiswa.',
        ],
    ];
@endphp

@section('content')
<div class="kb-container kb-contact">
    {{-- RN-11 (kalimat ajakan, sentence case) dan RN-12 (pengantar) --}}
    <x-ui.page-header title="Kontak" description="Ingin berkolaborasi dengan Kolabora? Hubungi kami di sini." />

    <div class="kb-contact__grid">
        <section aria-labelledby="kontak-tim">
            <h2 id="kontak-tim" class="kb-contact__h2">Tim</h2>
            <p class="kb-contact__intro">Platform Kolabora dikembangkan oleh tim berikut.</p>

            <ul class="kb-contact__channels">
                @foreach ($teamMembers as $member)
                    <li>
                        <span class="kb-contact__name">{{ $member['name'] }}</span>
                        @if (filled($member['email']))
                            <a href="mailto:{{ $member['email'] }}" class="kb-contact__email">
                                <x-icon name="mail" size="sm" />{{ $member['email'] }}
                            </a>
                        @else
                            <span class="kb-contact__meta">Tim {{ $member['team'] }} · {{ $member['campus'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p class="kb-contact__note">
                <x-icon name="info" size="sm" />
                <span>Ingin tahu lebih banyak tentang tim? Lihat <a href="{{ route('about') }}#about-tim">Tentang Kami</a>.</span>
            </p>
        </section>

        <section aria-labelledby="kontak-faq">
            <h2 id="kontak-faq" class="kb-contact__h2">Pertanyaan umum</h2>
            <div class="kb-faq">
                @foreach ($faqs as $faq)
                    <details class="kb-faq__item">
                        <summary>
                            <span>{{ $faq['q'] }}</span>
                            <x-icon name="chevron-down" size="sm" class="kb-faq__chevron" />
                        </summary>
                        <p>{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
