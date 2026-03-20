@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Хурлын зар')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">{{ $listTitle ?? 'Миний оролцох хурлын зарууд' }}</h1>
    </div>

    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Огноо</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Гарчиг</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Хэрэг</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Танхим</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hearings as $hearing)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-slate-700">{{ optional($hearing->start_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $hearing->title ?? $hearing->hearing_state ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $hearing->case_no ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $hearing->courtroom ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $hearing->status ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Оролцох хурал олдсонгүй.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/30">
            {{ $hearings->links() }}
        </div>
    </div>
</div>
@endsection
