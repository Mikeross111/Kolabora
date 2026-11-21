{{-- <head> bersama layouts.app dan layouts.auth (phase 3). pola judul: "Halaman — Kolabora" (D-01) --}}
@php
    // akhiran merek lama yang ditulis halaman sendiri (" - Laravel", " - " . app.name) dibuang di sini, bukan di tiap view
    $kbPageTitle = trim($__env->yieldContent('title'));
    $kbPageTitle = trim(preg_replace('/\s*[-–—|]\s*(Laravel|Kolabora|'.preg_quote((string) config('app.name'), '/').')\s*$/iu', '', $kbPageTitle));
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="kb-sprite" content="{{ asset('assets/icons/ui/sprite.svg') }}">

<title>{{ $kbPageTitle !== '' ? $kbPageTitle : 'Platform Digital untuk Kuliah Kerja Nyata' }} — Kolabora</title>

<link rel="icon" href="{{ asset('assets/brand/favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('assets/brand/favicon.ico') }}" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('assets/brand/apple-touch-icon.png') }}">

{{-- font lokal (OFL); satu file variabel untuk teks antarmuka di atas lipatan --}}
<link rel="preload" as="font" type="font/woff2" crossorigin href="{{ asset('assets/fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2') }}">

{{-- bundel aplikasi lebih dulu (modul = defer), lalu alpine: pendaftaran alpine:init sudah siap --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
