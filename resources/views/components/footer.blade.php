{{-- resources/views/components/footer.blade.php (phase 3)
     permukaan gelap .kb-on-dark: teks #f1f8f7 / #cfe6e3 di atas #0b3b3a (kontras >= 4.5:1).
     tautan 'Kebijakan Privasi' (sebelumnya ke '#') disembunyikan sampai halamannya ada (D-23). --}}
<footer class="kb-on-dark kb-footer">
    <div class="kb-container">
        <div class="kb-footer__top">
            <div>
                <a href="{{ route('home') }}" class="kb-footer__brand inline-block" aria-label="Kolabora, ke beranda">
                    <img src="{{ asset('assets/brand/kolabora-logo-on-dark.svg') }}" alt="" width="99" height="40">
                </a>
                <p class="kb-footer__tagline">
                    Platform digital yang menghubungkan mahasiswa dengan instansi untuk program Kuliah Kerja Nyata berkelanjutan.
                </p>
            </div>

            <nav class="kb-footer__nav" aria-label="Tautan kaki halaman">
                <h2>Jelajahi</h2>
                <ul>
                    <li><a href="{{ route('home') }}">Beranda</a></li>
                    <li><a href="{{ route('about') }}">Tentang Kami</a></li>
                    <li><a href="{{ route('contact') }}">Kontak</a></li>
                </ul>
            </nav>
        </div>

        <p class="kb-footer__legal">
            Hak cipta © {{ date('Y') }}. Seluruh hak cipta dilindungi undang-undang dan terdaftar pada Direktorat Jendral Kekayaan Intelektual Republik Indonesia.
        </p>
    </div>
</footer>
