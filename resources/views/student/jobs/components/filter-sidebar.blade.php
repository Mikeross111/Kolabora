{{-- panel filter lowongan (phase 15): pola dan kelas sama dengan telusuri masalah (phase 8).
     satu form GET dengan nama parameter yang sudah dibaca JobController@index: search, category, sdg[], sort
     (+ location, job_type[], salary_min bila sudah ada di url, sebagai input tersembunyi agar tidak hilang).
     >= 1024px: kolom kiri, perubahan langsung diterapkan; < 1024px: drawer (kbFilterBar) dengan "Terapkan" yang lengket. --}}

<div class="kb-browse-filters__backdrop" :data-open="open ? '' : null" @click="close()" aria-hidden="true"></div>
<form method="GET" action="{{ route('student.jobs.index') }}" id="jobs-filters" class="kb-browse-filters"
      x-ref="panel" :data-open="open ? '' : null" :role="open ? 'dialog' : null" :aria-modal="open ? 'true' : null"
      aria-labelledby="jobs-filters-title"
      x-on:change="if ($event.target.form === $el && window.matchMedia('(min-width: 1024px)').matches) $nextTick(() => $el.requestSubmit())">

    {{-- parameter lama yang tidak punya kontrol di panel ini tetap ikut terkirim --}}
    @if (filled(request('location')))
        <input type="hidden" name="location" value="{{ request('location') }}">
    @endif
    @foreach ($selectedTypes as $type)
        <input type="hidden" name="job_type[]" value="{{ $type }}">
    @endforeach
    @if (filled(request('salary_min')))
        <input type="hidden" name="salary_min" value="{{ request('salary_min') }}">
    @endif

    <div class="kb-browse-filters__head">
        <h2 id="jobs-filters-title">Filter</h2>
        @if (count($activeFilters))
            <a href="{{ $clearUrl }}" class="kb-browse__clear kb-browse-filters__reset">Atur ulang</a>
        @endif
        <button type="button" class="kb-icon-btn kb-browse-filters__close" @click="close()" aria-label="Tutup filter"><x-icon name="x" /></button>
    </div>

    <div class="kb-browse-filters__body">
        <x-ui.field label="Bidang" for="filter-category">
            <x-ui.select name="category" id="filter-category" :options="$categories->pluck('name', 'id')->all()"
                         :selected="request('category')" placeholder="Semua bidang" />
        </x-ui.field>

        {{-- tujuan SDG: 17 nama bahasa indonesia + swatch warna resmi; label terlihat dan tertaut ke input.
             target: seluruh baris label (>= 32px tinggi; 44px pada layar sentuh lewat .kb-check) --}}
        <details class="kb-browse-filters__details" @if (count($selectedSdgs)) open @endif>
            <summary>
                Tujuan SDG
                @if (count($selectedSdgs))<span class="kb-browse-filters__badge">{{ count($selectedSdgs) }} dipilih</span>@endif
                <x-icon name="chevron-down" size="sm" class="kb-chevron" />
            </summary>
            <fieldset aria-describedby="filter-sdg-help">
                <legend class="sr-only">Tujuan SDG</legend>
                <p class="kb-help" id="filter-sdg-help">Lowongan harus memuat semua tujuan yang dipilih.</p>
                <div class="grid pt-1">
                    @foreach ($sdgOptions as $value => $label)
                        <label class="kb-check kb-sdg py-1.5" data-sdg="{{ $value }}" for="filter-sdg-{{ $value }}">
                            <input type="checkbox" name="sdg[]" id="filter-sdg-{{ $value }}" value="{{ $value }}" @checked(in_array($value, $selectedSdgs, true))>
                            <span class="kb-chip__swatch mt-1.5" aria-hidden="true"></span>
                            <span>{{ $value }}. {{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </details>
    </div>

    <div class="kb-browse-filters__foot">
        <button type="submit" class="kb-btn kb-btn--primary">Terapkan</button>
        <a href="{{ $clearUrl }}" class="kb-btn kb-btn--tertiary">Atur ulang</a>
    </div>
</form>
