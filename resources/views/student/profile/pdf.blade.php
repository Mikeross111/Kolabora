{{-- templat PDF portofolio: dirender dompdf (tanpa flexbox/grid, tanpa sumber daya jarak jauh). QR dikirim sebagai data URI PNG. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $student->first_name }} {{ $student->last_name }} - Portofolio Kolabora</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.55; color: #16293d; }
        h1 { margin: 0 0 2px; font-size: 22px; color: #103050; }
        h2 { margin: 0 0 6px; font-size: 13px; color: #0f7d73; border-bottom: 1px solid #c9d6d3; padding-bottom: 3px; }
        h3 { margin: 0 0 2px; font-size: 12px; color: #16293d; }
        p { margin: 0 0 4px; }
        .muted { color: #566b73; }
        .small { font-size: 9.5px; color: #7b8e92; }
        .stripe { height: 5px; background: #0f7d73; margin-bottom: 14px; }
        table.head { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        td.initials { width: 56px; height: 56px; background: #0f7d73; color: #ffffff; text-align: center; font-size: 20px; font-weight: bold; }
        td.qr { width: 84px; text-align: right; vertical-align: top; }
        td.qr img { width: 76px; height: 76px; }
        .section { margin-bottom: 14px; }
        .tag { display: inline-block; margin: 0 4px 4px 0; padding: 2px 8px; background: #f1f8f7; color: #0f7d73; border: 1px solid #c9d6d3; font-size: 10px; }
        .project { margin-bottom: 8px; padding: 7px 9px; background: #f5f8f8; border-left: 3px solid #0f7d73; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #c9d6d3; text-align: center; }
    </style>
</head>
<body>
    <div class="stripe"></div>

    <table class="head">
        <tr>
            <td class="initials">{{ $student->initials }}</td>
            <td style="padding-left: 12px; vertical-align: middle;">
                <h1>{{ $student->first_name }} {{ $student->last_name }}</h1>
                <p class="muted">
                    @if ($student->university){{ $student->major ?? 'Mahasiswa' }} &middot; {{ $student->university->name }}@else Mahasiswa @endif
                    @if ($student->semester) &middot; Semester {{ $student->semester }}@endif
                </p>
                <p class="small">{{ $user->email }}@if ($student->phone) &middot; {{ $student->phone }}@endif</p>
            </td>
            <td class="qr">
                <img src="{{ $qrPng }}" alt="Kode QR portofolio">
                <div class="small">Pindai untuk portofolio</div>
            </td>
        </tr>
    </table>

    @if ($student->bio)
        <div class="section">
            <h2>Tentang saya</h2>
            <p class="muted">{{ $student->bio }}</p>
        </div>
    @endif

    @if ($student->skills && count($student->skills) > 0)
        <div class="section">
            <h2>Keahlian</h2>
            @foreach ($student->skills as $skill)<span class="tag">{{ $skill }}</span>@endforeach
        </div>
    @endif

    @if ($student->interests && count($student->interests) > 0)
        <div class="section">
            <h2>Minat</h2>
            @foreach ($student->interests as $interest)<span class="tag">{{ $interest }}</span>@endforeach
        </div>
    @endif

    @if (isset($projects) && $projects->count() > 0)
        <div class="section">
            <h2>Proyek yang diselesaikan</h2>
            @foreach ($projects->take(8) as $project)
                <div class="project">
                    <h3>{{ $project->title }}</h3>
                    <p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags((string) $project->description), 170) }}</p>
                    <p class="small">
                        @if ($project->institution){{ $project->institution->name }} &middot; @endif
                        @if ($project->start_date){{ $project->start_date->locale('id')->translatedFormat('M Y') }}@endif
                        @if ($project->end_date) &ndash; {{ $project->end_date->locale('id')->translatedFormat('M Y') }}@endif
                        @if ($project->rating) &middot; Penilaian instansi: {{ (int) $project->rating }}/5 @endif
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($student->stories && count($student->stories) > 0)
        <div class="section">
            <h2>Cerita pengalaman</h2>
            @foreach (array_slice((array) $student->stories, 0, 3) as $story)
                <p class="muted">{{ \Illuminate\Support\Str::limit(trim((string) $story), 500) }}</p>
            @endforeach
        </div>
    @endif

    <div class="footer small">
        Portofolio publik: {{ $profileUrl }}<br>
        Dibuat dengan Kolabora &middot; {{ now()->locale('id')->translatedFormat('j F Y') }}
    </div>
</body>
</html>
