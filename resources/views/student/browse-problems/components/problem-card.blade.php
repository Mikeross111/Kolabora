{{-- kartu galeri bergaya katalog: foto sampul di atas, lencana di atas foto, seluruh kartu bisa diklik (satu tautan pada judul).
     tanpa foto atau foto gagal dimuat: latar bermotif merek tampil sebagai pengganti, tinggi kartu tetap sama. --}}
@php
    $level = $difficulties[$problem->difficulty_level] ?? null;
    $sdgs = array_values(array_filter(array_map('intval', (array) $problem->sdg_categories)));
    $deadline = $problem->application_deadline;
    $applied = $applicationStatuses[$problem->id] ?? null;
    $image = optional($problem->images->first())->image_path;
    $where = collect([optional($problem->regency)->name, optional($problem->province)->name])->filter()->implode(', ');
    $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false) : null;
@endphp
<article class="kb-pcard kb-browse-card" data-problem-card="{{ $problem->id }}">
    <div class="kb-pcard__media">
        @if ($image)
            <img src="{{ supabase_url($image) }}" alt="" loading="lazy" width="800" height="600" onerror="this.hidden = true">
        @endif
        @if ($problem->is_urgent || $problem->is_featured)
            <p class="kb-pcard__badges">
                @if ($problem->is_urgent)<span class="kb-pcard__badge"><x-icon name="clock" size="sm" />Mendesak</span>@endif
                @if ($problem->is_featured)<span class="kb-pcard__badge"><x-icon name="award" size="sm" />Unggulan</span>@endif
            </p>
        @endif
    </div>
    <div class="kb-pcard__body">
        <p class="kb-browse-card__by">{{ optional($problem->institution)->name }}</p>
        <h3 class="kb-pcard__title"><a class="kb-pcard__link" href="{{ route('student.browse-problems.show', $problem->id) }}">{{ $problem->title }}</a></h3>
        @if ($applied)
            <p class="kb-pcard__status"><x-ui.status domain="application" :value="$applied" /></p>
        @endif
        <p class="kb-pcard__where"><x-icon name="map-pin" size="sm" />{{ $where ?: '—' }}</p>
        <dl class="kb-browse-card__facts">
            <div><dt>Kuota</dt><dd>{{ $problem->required_students }} mahasiswa</dd></div>
            <div><dt>Tenggat</dt><dd>@if ($deadline)<time datetime="{{ $deadline->toDateString() }}">{{ $deadline->locale('id')->translatedFormat('j M Y') }}</time>@if ($daysLeft !== null)<span class="kb-pcard__left"> · {{ $daysLeft > 0 ? $daysLeft . ' hari lagi' : ($daysLeft === 0 ? 'hari ini' : 'ditutup') }}</span>@endif @else — @endif</dd></div>
            @if ($level)
                <div><dt>Kesulitan</dt><dd><span class="kb-meter" data-level="{{ $level[0] }}"><span class="kb-meter__bars" aria-hidden="true"><i></i><i></i><i></i></span>{{ $level[1] }}</span></dd></div>
            @endif
        </dl>
        @if (count($sdgs))
            <ul class="kb-browse-row__sdgs" aria-label="Tujuan SDG">
                @foreach (array_slice($sdgs, 0, 2) as $sdg)
                    <li><x-ui.chip>SDG {{ $sdg }} · {{ \App\Http\Controllers\HomeController::sdgName($sdg) }}</x-ui.chip></li>
                @endforeach
                @if (count($sdgs) > 2)
                    <li><x-ui.chip>+{{ count($sdgs) - 2 }}</x-ui.chip></li>
                @endif
            </ul>
        @endif
    </div>
</article>
