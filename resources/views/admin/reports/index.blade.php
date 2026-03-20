@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Тайлан')

@section('content')
<div class="space-y-6">
    <h1 class="text-lg font-semibold text-slate-800">Тайлан</h1>

    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Эхлэх огноо</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Дуусах огноо</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div class="min-w-[220px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Нарийн бичиг</label>
                <select name="clerk_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <option value="">— Бүгд —</option>
                    @foreach($clerks as $c)
                        <option value="{{ $c->id }}" @selected((string)$clerkId === (string)$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                    Харах
                </button>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Цэвэрлэх
                </a>
                <a href="{{ route('admin.reports.download', request()->query()) }}"
                   class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition-colors">
                    Excel татах
                </a>
            </div>
            <div class="text-xs text-slate-500 w-full">
                - Excel дээд тал нь {{ number_format($exportLimit ?? 0) }} мөр.
            </div>
        </form>
    </div>

    @include('partials.widgets.notes-handover-stats', [
        'monthTotalHearings' => $summary['total'] ?? 0,
        'monthIssuedHearings' => $summary['issued'] ?? 0,
        'monthPendingHearings' => $summary['pending'] ?? 0,
    ])

    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-800">Шүүх хуралдааны шийдвэрүүд</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-auto min-w-[520px]">
                <thead>
                    <tr class="bg-white border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Шийдвэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Тоо</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decisionRows as $row)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-2.5 text-slate-700">{{ $row['name'] }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-700 tabular-nums">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

