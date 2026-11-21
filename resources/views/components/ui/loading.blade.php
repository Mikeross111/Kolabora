{{-- pemuatan: inline (spinner + teks, role=status) atau kerangka statis (lines > 0, tanpa animasi kilau) --}}
@props(['label' => 'Memuat…', 'lines' => 0])
@if ($lines > 0)
    <div {{ $attributes->class('grid gap-2') }} role="status" aria-label="{{ $label }}">
        <span class="kb-skeleton kb-skeleton--title"></span>
        @for ($i = 0; $i < $lines; $i++)
            <span class="kb-skeleton" @style(['width: '.(100 - ($i % 3) * 15).'%'])></span>
        @endfor
    </div>
@else
    <span {{ $attributes->class('kb-loading') }} role="status"><span class="kb-spinner" aria-hidden="true"></span>{{ $label }}</span>
@endif
