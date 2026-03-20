@extends('layouts.dashboard')
@section('header','Хурлын зар засварлах')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">Хурлын зарууд</h1>
        <a href="{{ route('admin.hearings.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition-colors shadow-sm">
            + Хурлын зар оруулах
        </a>
    </div>

    @isset($hearingStateCounts)
        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Хурлын төлөвөөр зарлагдсан тоо</h2>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach($states ?? [] as $state)
                    <span class="inline-flex items-center gap-2 text-sm">
                        <span class="text-slate-600">{{ $state }}:</span>
                        <span class="font-semibold text-slate-900">{{ $hearingStateCounts[$state] ?? 0 }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endisset

    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Огноо</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Төлөв</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Хэрэг</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Танхим</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Статус</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Үйлдэл</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hearings as $h)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-slate-700">{{ optional($h->start_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->hearing_state }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->case_no ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->courtroom ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $h->status }}</td>
                            <td class="px-4 py-3 text-right">
                                @role('admin')
                                    <a href="{{ route('admin.hearings.edit', $h) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition-colors">
                                        Засварлах
                                    </a>
                                @endrole
                                @role('secretary')
                                    @if ((int)$h->created_by === (int)auth()->id())
                                        <a href="{{ route('secretary.hearings.edit', $h) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition-colors">
                                            Засварлах
                                        </a>
                                    @else
                                        <span class="text-slate-300 text-sm">—</span>
                                    @endif
                                @endrole
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
