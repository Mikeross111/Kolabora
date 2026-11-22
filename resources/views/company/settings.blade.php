@extends('layouts.app')

@section('title', 'Pengaturan akun')

@section('content')
<div class="kb-container pb-16">
    <x-ui.page-header title="Pengaturan akun" description="Atur notifikasi untuk akun {{ $company->name }}."
                      :back="route('company.profile.index')" back-label="Kembali ke profil" />

    @if (session('success'))
        <x-ui.alert tone="success" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('company.profile.update-settings') }}" class="grid max-w-xl gap-4">
        @csrf
        @method('PUT')
        <fieldset class="grid gap-3 border-0 p-0">
            <legend class="mb-1 text-base font-semibold text-gray-900">Notifikasi</legend>
            <x-ui.checkbox name="email_notifications" id="set-email" value="1" label="Kirim notifikasi ke email perusahaan" :checked="$settings['email_notifications']" />
            <x-ui.checkbox name="application_notifications" id="set-lamaran" value="1" label="Beri tahu saya saat ada pelamar baru" :checked="$settings['application_notifications']" />
            <x-ui.checkbox name="marketing_emails" id="set-info" value="1" label="Kirim kabar dan tips Kolabora" :checked="$settings['marketing_emails']" />
        </fieldset>
        <div><x-ui.button type="submit">Simpan pengaturan</x-ui.button></div>
    </form>
</div>
@endsection
