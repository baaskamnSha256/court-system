@extends('layouts.dashboard')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
    @php
        $tab = $tab ?? null;
        $hasActiveTab = in_array($tab, ['notes_handover', 'decision_summary', 'article', 'punishment'], true);
        $tabLinks = [
            'notes_handover' => [
                'label' => 'Тэмдэглэл хүлээлцэх',
                'desc' => 'Хүлээлцсэн/хүлээлцээгүй тэмдэглэлийн ерөнхий үзүүлэлт',
                'iconBg' => 'bg-emerald-100',
                'iconText' => 'text-emerald-600',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z',
            ],
            'decision_summary' => [
                'label' => 'Шийдвэрийн тойм',
                'desc' => 'Шүүх хуралдааны шийдвэрийн ангиллаарх тоон дүн',
                'iconBg' => 'bg-indigo-100',
                'iconText' => 'text-indigo-600',
                'icon' => 'M9 17v-6m3 6V7m3 10v-4m3 8H6a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v11a2 2 0 01-2 2z',
            ],
            'article' => [
                'label' => 'Зүйл анги',
                'desc' => 'Шийдвэрлэсэн зүйл ангийн тархалт',
                'iconBg' => 'bg-violet-100',
                'iconText' => 'text-violet-600',
                'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            ],
            'punishment' => [
                'label' => 'Ялын төрөл',
                'desc' => 'Ялын төрөл болон зүйл ангийн хосолсон дүн',
                'iconBg' => 'bg-amber-100',
                'iconText' => 'text-amber-600',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4m8-8h-4M8 12H4',
            ],
        ];
    @endphp

        @if(! $hasActiveTab)
            <p class="text-gray-600">Тайлангийн төрлийг сонгоод тус бүрийн дэлгэрэнгүй рүү орно.</p>
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                @foreach($tabLinks as $key => $item)
                    <a href="{{ route('admin.reports.index', ['tab' => $key]) }}"
                       class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 transition-colors hover:border-gray-300 hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $item['iconBg'] }}">
                                <svg class="h-5 w-5 {{ $item['iconText'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $item['label'] }}</div>
                                <div class="text-sm text-gray-500">{{ $item['desc'] }}</div>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        @else
            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-sm font-medium text-slate-700">
                    {{ $tabLinks[$tab]['label'] ?? '' }}
                </div>
                <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    Тайлангийн цэс рүү
                </a>
            </div>
        @endif
    </div>

    @php
        $activeClerkId = $tab === 'notes_handover' ? $clerkId : null;
        $reportQuery = array_filter([
            'tab' => $tab,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'clerk_id' => $activeClerkId,
            'notes_decision_status' => $tab === 'decision_summary' && request()->filled('notes_decision_status')
                ? request('notes_decision_status')
                : null,
        ], fn ($v) => $v !== null && $v !== '');
    @endphp

    @if($tab === 'notes_handover')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
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
                            <option value="{{ $c->id }}" @selected((string)$activeClerkId === (string)$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                        Харах
                    </button>
                    <a href="{{ route('admin.reports.index', ['tab' => $tab]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Цэвэрлэх
                    </a>
                    <a href="{{ route('admin.reports.download', $reportQuery) }}"
                       class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition-colors">
                        Excel татах
                    </a>
                </div>
            </form>
            <div class="text-xs text-slate-500">
                - Excel дээд тал нь {{ number_format($exportLimit ?? 0) }} мөр.
            </div>
        </div>

        @include('partials.widgets.notes-handover-stats', [
            'monthTotalHearings' => $summary['total'] ?? 0,
            'monthIssuedHearings' => $summary['issued'] ?? 0,
            'monthPendingHearings' => $summary['pending'] ?? 0,
            'notesHandoverFilterBaseUrl' => route('admin.notes.index', array_filter([
                'hearing_date_from' => $dateFrom,
                'hearing_date_to' => $dateTo,
                'clerk_id' => $clerkId,
            ], fn ($v) => $v !== null && $v !== '')),
        ])
    @endif

    @if($tab === 'decision_summary')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if(request()->filled('notes_decision_status'))
                    <input type="hidden" name="notes_decision_status" value="{{ request('notes_decision_status') }}">
                @endif
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
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                        Харах
                    </button>
                    <a href="{{ route('admin.reports.index', ['tab' => $tab]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Цэвэрлэх
                    </a>
                    <a href="{{ route('admin.reports.download', $reportQuery) }}"
                       class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition-colors">
                        Excel татах
                    </a>
                </div>
            </form>
            <div class="text-xs text-slate-500">
                - Excel дээд тал нь {{ number_format($exportLimit ?? 0) }} мөр.
            </div>
        </div>

        @include('partials.widgets.report-decision-stats', [
            'decisionOptions' => $decisionOptions ?? [],
            'decisionCounts' => $decisionCounts ?? [],
            'decisionFilterBaseUrl' => $decisionFilterBaseUrl ?? null,
            'activeDecisionFilter' => $decisionStatusFilter ?? null,
            'totalScheduledHearingsInPeriod' => $totalScheduledHearingsInPeriod ?? ($summary['total'] ?? 0),
            'totalScheduledHearingsUrl' => $totalScheduledHearingsUrl ?? null,
            'totalScheduledHearingsDateFrom' => $dateFrom ?? null,
            'totalScheduledHearingsDateTo' => $dateTo ?? null,
        ])

        @include('partials.widgets.report-decision-hearings-list', [
            'decisionSummaryHearings' => $decisionSummaryHearings ?? null,
            'decisionStatusFilterLabel' => $decisionStatusFilterLabel ?? null,
            'dateFrom' => $dateFrom ?? null,
            'dateTo' => $dateTo ?? null,
            'matterNamesById' => $matterNamesById ?? collect(),
        ])
    @endif

    @if($tab === 'article')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
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
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                        Харах
                    </button>
                    <a href="{{ route('admin.reports.index', ['tab' => $tab]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Цэвэрлэх
                    </a>
                    <a href="{{ route('admin.reports.download', $reportQuery) }}"
                       class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition-colors">
                        Excel татах
                    </a>
                </div>
            </form>
            <div class="text-xs text-slate-500">
                - Энэ хэсэг нийт хурлын статистикаар гарна (нарийн бичгээр шүүхгүй).
            </div>
            <div class="text-xs text-slate-500">
                - Excel дээд тал нь {{ number_format($exportLimit ?? 0) }} мөр.
            </div>
        </div>

        @include('admin.reports._article_category_table', [
            'articleRows' => $articleRows ?? [],
            'articleTableColumns' => $articleTableColumns ?? null,
        ])
    @endif

    @if($tab === 'punishment')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
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
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                        Харах
                    </button>
                    <a href="{{ route('admin.reports.index', ['tab' => $tab]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Цэвэрлэх
                    </a>
                    <a href="{{ route('admin.reports.download.defendant-details', $reportQuery) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                        Шүүгдэгчийн дэлгэрэнгүй файл
                    </a>
                </div>
            </form>
            <div class="text-xs text-slate-500">
                - «Шүүгдэгчийн дэлгэрэнгүй файл» татахад дээд тал нь {{ number_format($exportLimit ?? 0) }} мөр.
            </div>
        </div>
        @include('admin.reports._defendant_detail_table')
    @endif
</div>
@endsection

