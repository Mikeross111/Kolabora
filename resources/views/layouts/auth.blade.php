<!DOCTYPE html>
<html lang="id">
<head>
    {{-- phase 6: satu template untuk masuk, pilih peran, tiga registrasi, lupa/reset password dan verifikasi email.
         head bersama (judul "… — Kolabora", favicon, vite app.css + app.js, alpine). --}}
    @include('partials.head')
    @stack('styles')
</head>
<body class="font-sans antialiased kb-auth-body">
    <a href="#main" class="kb-skip">Lewati ke konten utama</a>

    {{-- kepala ringkas: wordmark ke beranda + tautan umum (pengganti "Back to Home / Contact / About" lama) --}}
    <header class="kb-auth-header">
        <div class="kb-auth-header__bar">
            <a href="{{ route('home') }}" class="kb-auth-header__brand" aria-label="Kolabora, ke beranda">
                <img src="{{ asset('assets/brand/kolabora-logo.svg') }}" alt="" width="99" height="40">
            </a>
            <nav class="kb-auth-header__nav" aria-label="Tautan umum">
                <a href="{{ route('about') }}">Tentang Kami</a>
                <a href="{{ route('contact') }}">Kontak</a>
            </nav>
        </div>
    </header>

    <div class="kb-auth @hasSection('aside') kb-auth--split @endif">
        <main id="main" tabindex="-1" class="kb-auth__main">
            <div class="kb-auth__body {{ trim($__env->yieldContent('auth_width')) === 'wide' ? 'kb-auth__body--wide' : '' }}">
                {{-- pesan flash: kunci session sama seperti sebelumnya (success, error, info, warning, status) --}}
                @php
                    $kbFlash = array_filter([
                        'success' => session('success') ?: session('status'),
                        'danger' => session('error'),
                        'info' => session('info'),
                        'warning' => session('warning'),
                    ]);
                @endphp
                @foreach ($kbFlash as $tone => $message)
                    <x-ui.alert :tone="$tone" class="kb-auth__flash">{{ $message }}</x-ui.alert>
                @endforeach

                @yield('content')
            </div>
        </main>

        @hasSection('aside')
            {{-- panel samping (>= 1024px): wordmark + motif kontur, tanpa foto dan tanpa angka (D-19, J9) --}}
            <aside class="kb-auth__aside kb-contour" aria-label="Tentang Kolabora">
                <div class="kb-auth__aside-inner">
                    @yield('aside')
                </div>
            </aside>
        @endif
    </div>

    @stack('scripts')
</body>
</html>
