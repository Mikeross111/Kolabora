{{-- baris daftar: slot leading (avatar/ikon), judul (tautan bila href), meta, slot end (status/aksi) --}}
@props(['title', 'href' => null, 'meta' => null])
<li {{ $attributes }}>
    <div class="kb-list__row">
        {{ $leading ?? '' }}
        <div class="kb-list__main">
            <p class="kb-list__title">
                @if ($href)
                    <a href="{{ $href }}" class="hover:underline">{{ $title }}</a>
                @else
                    {{ $title }}
                @endif
            </p>
            @if ($meta)<p class="kb-list__meta">{{ $meta }}</p>@endif
            {{ $slot }}
        </div>
        @isset($end)
            <div class="kb-list__end">{{ $end }}</div>
        @endisset
    </div>
</li>
