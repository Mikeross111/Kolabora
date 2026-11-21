{{-- resources/views/student/chatbot/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kora')

{{-- phase 16: Kora (Asisten AI Kolabora), dua kolom: riwayat di kiri, ruang percakapan di kanan.
     tanpa hero foto. membuka/menyegarkan halaman ini tidak membuat percakapan; "Percakapan baru" = POST student.chatbot.store (D-25).
     contoh pertanyaan hanya teks petunjuk (bukan data), sesuai cakupan asisten. --}}

@section('content')
<div class="kb-container kb-chat">
    <x-ui.page-header title="Kora" description="Asisten AI Kolabora. Tanya seputar proyek KKN dan dokumen di Repositori Pengetahuan.">
        <x-slot:actions>
            <form method="POST" action="{{ route('student.chatbot.store') }}">
                @csrf
                <x-ui.button type="submit" icon="plus">Percakapan baru</x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="kb-chat__layout">
        @include('student.chatbot.components.history', ['conversations' => $conversations, 'activeId' => null])

        <section class="kb-chat__pane kb-chat__welcome" aria-labelledby="chat-welcome-title">
            <span class="kb-chat__welcome-icon"><x-icon name="message-square-text" size="lg" /></span>
            <h2 id="chat-welcome-title">
                @if ($conversations->count() > 0)
                    Pilih percakapan atau mulai yang baru
                @else
                    Mulai percakapan pertama Anda dengan Kora
                @endif
            </h2>
            <p>Kora menjawab berdasarkan dokumen di Repositori Pengetahuan dan menyertakan sumbernya bila ada. Periksa kembali jawaban penting pada dokumen sumbernya.</p>
            <div class="kb-chat__examples">
                <h3>Contoh pertanyaan</h3>
                <ul>
                    <li>Bagaimana proses melamar proyek KKN di Kolabora?</li>
                    <li>Apa yang perlu disiapkan sebelum memulai proyek KKN di desa?</li>
                    <li>Ringkas laporan KKN tentang pengelolaan sampah di repositori.</li>
                </ul>
            </div>
            <form method="POST" action="{{ route('student.chatbot.store') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" icon="plus">Mulai percakapan</x-ui.button>
            </form>
        </section>
    </div>
</div>
@endsection
