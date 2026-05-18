@php
    $decisionOptions = $decisionOptions ?? [];
    $decisionCounts = $decisionCounts ?? [];
    $decisionFilterBaseUrl = $decisionFilterBaseUrl ?? null;
    $activeDecisionFilter = $activeDecisionFilter ?? null;
    $totalScheduledHearingsInPeriod = (int) ($totalScheduledHearingsInPeriod ?? 0);
    $totalScheduledHearingsUrl = $totalScheduledHearingsUrl ?? null;
    $totalScheduledHearingsDateFrom = $totalScheduledHearingsDateFrom ?? null;
    $totalScheduledHearingsDateTo = $totalScheduledHearingsDateTo ?? null;
    $totalScheduledPeriodActive = ($activeDecisionFilter ?? null) === null || ($activeDecisionFilter ?? '') === '';
    $totalCardRing = $totalScheduledPeriodActive ? 'ring-2 ring-blue-400/80' : '';

    $palette = [
        'Хүлээгдэж буй' => 'border-slate-200 bg-slate-50/60 text-slate-800',
        'Шийдвэрлэсэн' => 'border-emerald-200 bg-emerald-50/40 text-emerald-800',
        'Хойшилсон' => 'border-amber-200 bg-amber-50/40 text-amber-800',
        'Завсарласан' => 'border-sky-200 bg-sky-50/40 text-sky-800',
        'Түдгэлзүүлсэн' => 'border-orange-200 bg-orange-50/40 text-orange-800',
        'Прокурорт буцаасан' => 'border-rose-200 bg-rose-50/40 text-rose-800',
        'Яллагдагчийг шүүхэд шилжүүлсэн' => 'border-indigo-200 bg-indigo-50/40 text-indigo-800',
        '60 хүртэлх хоногоор хойшлуулсан' => 'border-violet-200 bg-violet-50/40 text-violet-800',
    ];
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3 text-sm font-semibold text-slate-800">Шүүх хуралдааны шийдвэрийн тойм</div>
    <div class="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(9.5rem,1fr))]">
            @if($totalScheduledHearingsUrl)
                <a href="{{ $totalScheduledHearingsUrl }}"
                   class="block min-w-0 rounded-xl border border-blue-200 bg-blue-50/50 p-3 text-blue-900 {{ $totalCardRing }} transition hover:ring-2 hover:ring-blue-300/60">
                    <div class="text-[11px] font-semibold leading-snug opacity-90 whitespace-normal break-words [text-wrap:wrap]">
                        Нийт зарлагдсан шүүх хурал
                    </div>
                    @if($totalScheduledHearingsDateFrom && $totalScheduledHearingsDateTo)
                        <div class="mt-0.5 text-[10px] font-medium leading-snug opacity-75">
                            {{ $totalScheduledHearingsDateFrom }} – {{ $totalScheduledHearingsDateTo }}
                        </div>
                    @endif
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($totalScheduledHearingsInPeriod) }}</div>
                </a>
            @else
                <div class="min-w-0 rounded-xl border border-blue-200 bg-blue-50/50 p-3 text-blue-900 {{ $totalCardRing }}">
                    <div class="text-[11px] font-semibold leading-snug opacity-90 whitespace-normal break-words [text-wrap:wrap]">
                        Нийт зарлагдсан шүүх хурал
                    </div>
                    @if($totalScheduledHearingsDateFrom && $totalScheduledHearingsDateTo)
                        <div class="mt-0.5 text-[10px] font-medium leading-snug opacity-75">
                            {{ $totalScheduledHearingsDateFrom }} – {{ $totalScheduledHearingsDateTo }}
                        </div>
                    @endif
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($totalScheduledHearingsInPeriod) }}</div>
                </div>
            @endif
        @foreach($decisionOptions as $key => $label)
            @php
                $cls = $palette[$key] ?? 'border-slate-200 bg-slate-50/40 text-slate-800';
                $filterValue = $key === 'Хүлээгдэж буй' ? '__pending__' : $key;
                $filterUrl = $decisionFilterBaseUrl
                    ? $decisionFilterBaseUrl . (str_contains($decisionFilterBaseUrl, '?') ? '&' : '?') . http_build_query(['notes_decision_status' => $filterValue])
                    : null;
            @endphp
            @php
                $isActive = $activeDecisionFilter !== null && $activeDecisionFilter === $filterValue;
                $activeRing = $isActive ? 'ring-2 ring-slate-500/80' : '';
            @endphp
            @if($filterUrl)
                <a href="{{ $filterUrl }}" class="block min-w-0 rounded-xl border p-3 {{ $cls }} {{ $activeRing }} transition hover:ring-2 hover:ring-slate-300/70">
                    <div class="text-[11px] font-semibold leading-snug opacity-80 whitespace-normal break-words [text-wrap:wrap]">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format((int) ($decisionCounts[$key] ?? 0)) }}</div>
                </a>
            @else
                <div class="min-w-0 rounded-xl border p-3 {{ $cls }}">
                    <div class="text-[11px] font-semibold leading-snug opacity-80 whitespace-normal break-words [text-wrap:wrap]">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format((int) ($decisionCounts[$key] ?? 0)) }}</div>
                </div>
            @endif
        @endforeach
    </div>
</div>
