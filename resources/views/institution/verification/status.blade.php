@extends('layouts.app')

@section('title', 'Status Verifikasi Dokumen')

@section('content')
@php
    // phase 29 paket D: ringkasan status. kalimat soal "AI" dipertahankan kata per kata (D-09 masih OPEN).
    // status instansi dari kosakata bersama (domain verification); hasil per dokumen = ai_status seperti sebelumnya.
    $waktu = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('j M Y, H.i') : null;
    $angka = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');
    $docLabels = [
        'official_letter' => 'Surat Pengantar / SK',
        'logo' => 'Logo Institusi',
        'pic_identity' => 'KTP Penanggung Jawab',
        'npwp' => 'NPWP',
    ];
    $flagLabels = ['error' => 'Masalah', 'warning' => 'Peringatan', 'info' => 'Info'];
@endphp

<div class="kb-container kb-inst-page">
    <x-ui.page-header title="Status verifikasi dokumen" :description="'Hasil verifikasi AI untuk '.$institution->name"
                      :back="route('institution.dashboard')" back-label="Kembali ke dashboard">
        <x-slot:actions>
            <x-ui.button :href="route('institution.verification.upload')" icon="upload">Unggah dokumen baru</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="kb-inst-verify kb-inst-verify--status">
        @if (session('success'))
            <x-ui.alert tone="success">{{ session('success') }}</x-ui.alert>
        @endif

        {{-- status keseluruhan --}}
        <section class="kb-inst-panel" aria-labelledby="judul-status-keseluruhan">
            <h2 id="judul-status-keseluruhan"><x-icon name="shield-check" />Status keseluruhan</h2>
            <x-ui.dl class="kb-inst-verify__facts">
                <div>
                    <dt>Status verifikasi</dt>
                    <dd>
                        @if (filled($institution->verification_status))
                            <x-ui.status domain="verification" :value="$institution->verification_status" />
                        @else
                            Belum Diverifikasi
                        @endif
                    </dd>
                </div>
                @if (! is_null($institution->verification_score))
                    <div>
                        <dt>Skor verifikasi</dt>
                        <dd class="kb-inst-verify__num">{{ $angka($institution->verification_score) }}/100</dd>
                    </div>
                @endif
                @if (! is_null($institution->verification_confidence))
                    <div>
                        <dt>Tingkat keyakinan</dt>
                        <dd class="kb-inst-verify__num">{{ number_format($institution->verification_confidence * 100, 0) }}%</dd>
                    </div>
                @endif
                @if ($institution->verified_at)
                    <div>
                        <dt>Terakhir diverifikasi</dt>
                        <dd class="kb-inst-verify__num">{{ $waktu($institution->verified_at) }} WIB</dd>
                    </div>
                @endif
            </x-ui.dl>
        </section>

        {{-- detail per dokumen --}}
        <section aria-labelledby="judul-detail-dokumen">
            <div class="kb-inst-checklist__head">
                <h2 id="judul-detail-dokumen">Detail dokumen</h2>
                @if ($documents->isNotEmpty())
                    <span class="kb-inst-checklist__count">{{ $documents->count() }} dokumen</span>
                @endif
            </div>

            @if ($documents->isEmpty())
                <x-ui.empty-state title="Belum ada dokumen yang diupload" description="Unggah dokumen resmi instansi agar dapat diperiksa.">
                    <x-slot:action>
                        <x-ui.button variant="secondary" :href="route('institution.verification.upload')">Unggah dokumen</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <ul class="kb-inst-docs">
                    @foreach ($documents as $doc)
                        <li class="kb-inst-docs__item">
                            <div class="kb-inst-docs__head">
                                <div class="kb-inst-docs__who">
                                    <h3>{{ $docLabels[$doc->document_type] ?? ucfirst($doc->document_type) }}</h3>
                                    <p class="kb-inst-docs__file">{{ $doc->file_name }}</p>
                                </div>
                                @if ($doc->ai_status)
                                    <x-ui.status domain="verification_document" :value="$doc->ai_status" />
                                @endif
                            </div>

                            @if ($doc->ai_score)
                                <p class="kb-inst-docs__score">AI Score <strong>{{ $doc->ai_score }}</strong></p>
                            @endif

                            @if ($doc->ai_processed_at)
                                <div class="kb-inst-docs__result">
                                    <h4>Hasil Analisis AI</h4>

                                    @if ($doc->ai_reasoning)
                                        <p class="kb-inst-docs__text">{{ $doc->ai_reasoning }}</p>
                                    @endif

                                    @if ($doc->ai_flags && count($doc->ai_flags) > 0)
                                        <p class="kb-inst-docs__label">Temuan:</p>
                                        <ul class="kb-inst-docs__flags">
                                            @foreach ($doc->ai_flags as $flag)
                                                <li>
                                                    <span class="kb-inst-flag" data-type="{{ $flag['type'] }}">{{ $flagLabels[$flag['type']] ?? ucfirst($flag['type']) }}</span>
                                                    <span>{{ $flag['message'] }}</span>
                                                    <span class="kb-inst-docs__meta">(Tingkat keparahan: {{ $flag['severity'] }}/10)</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($doc->ai_extracted_data && count($doc->ai_extracted_data) > 0)
                                        <p class="kb-inst-docs__label">Data yang Diekstrak:</p>
                                        <dl class="kb-inst-docs__data">
                                            @foreach ($doc->ai_extracted_data as $key => $value)
                                                <div>
                                                    <dt>{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                                    <dd>{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif

                                    <p class="kb-inst-docs__meta">Diproses: {{ $waktu($doc->ai_processed_at) }} WIB</p>
                                </div>
                            @else
                                <p class="kb-inst-docs__meta">Belum diverifikasi AI</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection
