@extends('layouts.app')

@section('title', 'Profil Perusahaan')

{{-- phase 25: profil organisasi standar (pola .kb-org dari phase 5, sama dengan profil instansi phase 20): kepala dengan
     logo atau inisial, grid fakta yang tahan teks panjang, kontak, media sosial, info akun. tanpa foto hero.
     angka ringkasan dari query controller; nilai nol tidak ditampilkan (guardrail 2). --}}

@php
    // label jumlah karyawan sama dengan registrasi; nilai lain (mis. '51-100' dari seeder) tampil apa adanya (D-06)
    $sizeLabels = ['1-10' => '1–10 karyawan', '11-50' => '11–50 karyawan', '51-200' => '51–200 karyawan', '201-500' => '201–500 karyawan', '501-1000' => '501–1.000 karyawan', '1000+' => 'Lebih dari 1.000 karyawan'];
    $size = $company->company_size ?? $company->employee_count;
    $sizeLabel = $size ? ($sizeLabels[$size] ?? $size . ' karyawan') : null;
    $industry = $company->industry === 'Retail' ? 'Ritel' : $company->industry;
    $place = collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ');
    // email kontak = email perusahaan; bila belum diisi, email akun (sama dengan isian awal form edit)
    $email = $company->email ?: $company->user?->email;
    // hanya tautan http(s) yang dijadikan link
    $link = fn ($url) => filled($url) && preg_match('#^https?://#i', $url) ? $url : null;
    $socials = array_filter([
        'LinkedIn' => $company->linkedin,
        'Twitter/X' => $company->twitter,
        'Facebook' => $company->facebook,
        'Instagram' => $company->instagram,
    ], fn ($url) => filled($url));
    $verification = $company->is_verified ? 'verified' : ($company->verification_status ?: 'pending_verification');
@endphp

@section('content')
<div class="kb-container kb-org kb-co-profile">
    @if (session('success'))
        {{-- pesan simpan controller (bahasa inggris) diterjemahkan; pesan lain tampil apa adanya --}}
        <x-ui.alert tone="success" class="mt-6">{{ session('success') === 'Profile updated successfully!' ? 'Profil perusahaan disimpan.' : session('success') }}</x-ui.alert>
    @endif
    @if (session('error'))
        <x-ui.alert tone="danger" class="mt-6">{{ session('error') }}</x-ui.alert>
    @endif

    <header class="kb-org__head">
        <x-ui.avatar :src="$company->logo ? $company->logo_url : null" :name="$company->name" size="xl" square decorative />
        <div class="kb-org__id">
            <p class="kb-org__kicker">Profil perusahaan</p>
            <h1 class="kb-org__name">{{ $company->name }}</h1>
            @if (filled($company->tagline))
                <p class="kb-co-profile__tagline">{{ $company->tagline }}</p>
            @endif
            <p class="kb-org__meta">
                @if ($industry)<span>{{ $industry }}</span>@endif
                @if ($place)<span class="kb-org__place"><x-icon name="map-pin" size="sm" />{{ $place }}</span>@endif
            </p>
            <x-ui.summary class="kb-org__summary" label="Ringkasan perusahaan" :items="[
                ['value' => $stats['active_jobs'] ?: null, 'label' => 'lowongan dibuka'],
                ['value' => $stats['total_jobs'] ?: null, 'label' => 'lowongan'],
                ['value' => $stats['total_applications'] ?: null, 'label' => 'pelamar'],
                ['value' => $stats['total_hires'] ?: null, 'label' => 'direkrut'],
            ]" />
            <div class="kb-co-profile__actions">
                <x-ui.button :href="route('company.profile.edit')" icon="pencil">Edit Profil</x-ui.button>
                <x-ui.button variant="secondary" :href="route('company.public', $company->id)" icon-end="external-link"
                             target="_blank" rel="noopener">Lihat Profil Publik<span class="sr-only"> (tab baru)</span></x-ui.button>
            </div>
        </div>
    </header>

    <div class="kb-org__grid">
        <div class="kb-org__main">
            <section class="kb-org__section" aria-labelledby="profil-tentang">
                <h2 id="profil-tentang" class="kb-org__h2">Tentang perusahaan</h2>
                @if (filled($company->description))
                    <p class="kb-org__about kb-co-profile__about">{{ $company->description }}</p>
                @else
                    <p class="kb-co-profile__hint">Belum ada deskripsi. Deskripsi tampil di profil publik;
                        <a href="{{ route('company.profile.edit') }}">tambahkan lewat Edit Profil</a>.</p>
                @endif
            </section>

            <section class="kb-org__section" aria-labelledby="profil-informasi">
                <h2 id="profil-informasi" class="kb-org__h2">Informasi perusahaan</h2>
                <dl class="kb-co-facts">
                    <div class="kb-co-facts__wide"><dt>Nama perusahaan</dt><dd>{{ $company->name }}</dd></div>
                    <div><dt>Industri</dt><dd>{{ $industry ?: '—' }}</dd></div>
                    <div><dt>Jumlah karyawan</dt><dd>{{ $sizeLabel ?? '—' }}</dd></div>
                    <div><dt>Tahun berdiri</dt><dd>{{ $company->founded_year ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="kb-org__section" aria-labelledby="profil-kontak">
                <h2 id="profil-kontak" class="kb-org__h2">Kontak dan alamat</h2>
                <dl class="kb-co-facts">
                    <div class="kb-co-facts__wide"><dt>Email</dt><dd>{{ $email ?: '—' }}</dd></div>
                    <div><dt>Nomor telepon</dt><dd>{{ $company->phone ?: '—' }}</dd></div>
                    <div class="kb-co-facts__wide">
                        <dt>Website</dt>
                        <dd>
                            @if ($link($company->website))
                                <a href="{{ $company->website }}" target="_blank" rel="noopener">{{ $company->website }}<span class="sr-only"> (tab baru)</span></a>
                            @elseif (filled($company->website))
                                {{ $company->website }}
                            @else
                                <span class="kb-co-facts__empty">Belum diisi</span>
                            @endif
                        </dd>
                    </div>
                    <div class="kb-co-facts__wide"><dt>Alamat</dt><dd>{{ $company->address ?: '—' }}</dd></div>
                    @if ($place)
                        <div><dt>Wilayah</dt><dd>{{ $place }}</dd></div>
                    @endif
                    @if (filled($company->location))
                        <div><dt>Lokasi</dt><dd>{{ $company->location }}</dd></div>
                    @endif
                    @if (filled($company->postal_code))
                        <div><dt>Kode pos</dt><dd>{{ $company->postal_code }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($socials)
                <section class="kb-org__section" aria-labelledby="profil-sosial">
                    <h2 id="profil-sosial" class="kb-org__h2">Media sosial</h2>
                    <dl class="kb-co-facts">
                        @foreach ($socials as $network => $url)
                            <div class="kb-co-facts__wide">
                                <dt>{{ $network }}</dt>
                                <dd>
                                    @if ($link($url))
                                        <a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}<span class="sr-only"> (tab baru)</span></a>
                                    @else
                                        {{ $url }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </div>

        {{-- info akun + status verifikasi dari kolom tersimpan (tanpa tombol yang tidak melakukan apa pun) --}}
        <aside class="kb-org__aside" aria-labelledby="profil-akun">
            <h2 id="profil-akun" class="kb-org__h2">Info akun</h2>
            <x-ui.status domain="verification" :value="$verification" />
            <form method="POST" action="{{ route('company.profile.request-verification') }}" enctype="multipart/form-data" class="mt-3 grid gap-2">
                @csrf
                <label class="kb-label" for="dokumen-verifikasi">Dokumen verifikasi (PDF/JPG/PNG, maks. 10 MB, opsional)</label>
                <input type="file" id="dokumen-verifikasi" name="verification_documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="kb-input">
                <x-ui.button type="submit" variant="secondary">{{ $company->is_verified ? 'Ajukan pembaruan verifikasi' : 'Ajukan verifikasi' }}</x-ui.button>
            </form>
            <p class="mt-3 mb-0"><a href="{{ route('company.settings') }}" class="text-brand-700 underline">Pengaturan akun</a></p>
            <dl class="kb-co-facts kb-co-facts--single">
                <div><dt>Email masuk</dt><dd>{{ $company->user?->email ?? '—' }}</dd></div>
                <div><dt>Bergabung sejak</dt><dd>{{ $company->created_at?->locale('id')->translatedFormat('F Y') ?? '—' }}</dd></div>
                <div><dt>Terakhir diperbarui</dt><dd>{{ $company->updated_at?->locale('id')->diffForHumans() ?? '—' }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
