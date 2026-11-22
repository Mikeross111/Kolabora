@extends('layouts.app')

@section('title', 'Log Validasi AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Log Validasi AI</h1>
                <p class="text-gray-600 mt-2">Riwayat setiap validasi otomatis yang dijalankan pada pendaftaran instansi</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 bg-white rounded-lg shadow-md p-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Jenis Validasi</label>
            <select name="validation_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach ($validationTypes as $type)
                    <option value="{{ $type }}" @selected(request('validation_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">API</label>
            <select name="api_used" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach ($apis as $api)
                    <option value="{{ $api }}" @selected(request('api_used') === $api)>{{ $api }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Terapkan</button>
        @if (request('validation_type') || request('api_used'))
            <a href="{{ route('admin.validation-logs') }}" class="text-sm text-gray-500 underline">Reset</a>
        @endif
    </form>

    <!-- Logs List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instansi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Validasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hasil</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">API</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Proses</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if ($log->institution)
                                <a href="{{ route('admin.institutions.review', $log->institution_id) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $log->institution->name }}
                                </a>
                            @else
                                <span class="text-sm text-gray-400">Instansi dihapus (#{{ $log->institution_id }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ ucwords(str_replace('_', ' ', $log->validation_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ number_format($log->score, 1) }}/100
                            @if ($log->confidence)
                                <div class="text-xs text-gray-400">keyakinan {{ number_format($log->confidence, 1) }}%</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($log->is_passed)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Lolos</span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Tidak lolos</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->api_used ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->processing_time ? $log->processing_time . ' ms' : '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <p class="mt-2 text-lg font-medium">Belum ada log validasi AI</p>
                            <p class="mt-2 text-sm">Log akan muncul setiap kali sistem menjalankan validasi otomatis pada pendaftaran instansi</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
