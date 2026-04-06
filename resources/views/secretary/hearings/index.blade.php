@extends('layouts.dashboard')
@section('header', 'Хурлын зар (Шүүгчийн туслах)')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">Миний хурлын зарууд</h1>
        <a href="{{ route('secretary.hearings.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition-colors shadow-sm">
            + Хурлын зар оруулах
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Огноо</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Гарчиг</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Шүүх хуралдааны шийдвэр</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Танхим</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Статус</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Үйлдэл</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hearings as $h)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-slate-700">{{ optional($h->start_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->title ?? $h->hearing_state ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->notes_decision_status ?: 'Хүлээгдэж буй' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->courtroom ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->status }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('secretary.hearings.edit', $h) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition-colors">
                                    Засварлах
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/30">
            {{ $hearings->links() }}
        </div>
    </div>
</div>
@endsection
