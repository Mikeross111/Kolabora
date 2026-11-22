{{-- resources/views/student/profile/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Profil Saya')

{{-- phase 12: profil sebagai portofolio yang bisa dibagikan. isi bersama profil publik (components/portfolio);
     di sini ditambah aksi pemilik: edit, salin tautan (toast), atur proyek yang tampil di publik.
     angka ringkasan dari query nyata ($stats, $statistics); angka nol tidak ditampilkan. --}}
@php
    $shareUrl = route('profile.public', $student->user->username);
    $summary = [
        ['value' => $stats['completed_projects'] ?: null, 'label' => 'proyek selesai'],
        ['value' => $stats['active_projects'] ?: null, 'label' => 'proyek berjalan'],
        ['value' => $statistics['sdgs_addressed'] ?: null, 'label' => 'SDG disentuh'],
    ];
@endphp

@section('content')
    @php $pct = (int) ($completeness['percentage'] ?? 100); $todo = (array) ($completeness['suggestions'] ?? []); @endphp
    @if ($pct < 100)
        <div class="kb-container">
            <section class="kb-dash-project__progress" aria-labelledby="judul-kelengkapan" style="display:grid; gap:.5rem; margin:1rem 0 0; padding:1rem; background:var(--kb-surface-raised); border:1px solid var(--kb-border-default); border-radius:var(--kb-radius-panel);">
                <h2 id="judul-kelengkapan" style="margin:0; font-size:1rem; line-height:1.5rem;">Kelengkapan profil</h2>
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <progress max="100" value="{{ $pct }}" aria-label="Kelengkapan profil" style="flex:1;">{{ $pct }}%</progress>
                    <strong>{{ $pct }}%</strong>
                </div>
                @if (count($todo))
                    <ul style="margin:0; padding-left:1.25rem;">
                        @foreach ($todo as $tip)<li>{{ $tip }}</li>@endforeach
                    </ul>
                @endif
                <p style="margin:0;"><a href="{{ route('student.profile.edit') }}">Lengkapi profil</a></p>
            </section>
        </div>
    @endif

    @include('student.profile.components.portfolio', [
        'projects' => $completedProjects,
        'owner' => true,
        'canViewContact' => true,
    ])
@endsection

@push('scripts')
<script>
// salin tautan portofolio (url asli profile.public) lalu konfirmasi lewat toast bersama
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-kb-copy]');
    if (!button) return;
    const url = button.dataset.kbCopy;
    const done = () => window.showNotification('Tautan portofolio disalin.', 'success');
    const failed = () => window.showNotification('Tautan belum tersalin. Salin manual: ' + url, 'error', 0);

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done).catch(() => (fallbackCopy(url) ? done() : failed()));
    } else {
        fallbackCopy(url) ? done() : failed();
    }
});

// cadangan untuk http lokal (clipboard api hanya di konteks aman)
function fallbackCopy(text) {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.className = 'sr-only';
    document.body.appendChild(area);
    area.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    area.remove();
    return ok;
}

// tampil/sembunyi proyek di profil publik (endpoint toggle-visibility yang sama)
document.addEventListener('alpine:init', () => {
    Alpine.data('kbPortfolioVisibility', (projectId, initial) => ({
        visible: initial,
        busy: false,
        toggle(event) {
            if (this.busy) return;
            this.busy = true;
            fetch(`/student/profile/project/${projectId}/toggle-visibility`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
                .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.success) throw new Error(data.message || 'Gagal mengubah tampilan proyek');
                    this.visible = !!data.is_visible;
                    window.showNotification(this.visible ? 'Proyek ditampilkan di profil publik.' : 'Proyek disembunyikan dari profil publik.', 'success');
                })
                .catch(() => {
                    window.showNotification('Tampilan proyek belum berubah. Coba lagi.', 'error');
                })
                .finally(() => {
                    this.busy = false;
                    if (event && event.target) event.target.checked = this.visible;
                });
        },
    }));
});
</script>
@endpush
