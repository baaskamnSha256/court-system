@php
    $decisionOptions = $decisionOptions ?? [];
    $decisionCounts = $decisionCounts ?? [];
    $decisionFilterBaseUrl = $decisionFilterBaseUrl ?? null;

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
    <div class="overflow-x-auto">
        <div class="flex min-w-max flex-nowrap gap-3">
        @foreach($decisionOptions as $key => $label)
            @php
                $cls = $palette[$key] ?? 'border-slate-200 bg-slate-50/40 text-slate-800';
                $filterValue = $key === 'Хүлээгдэж буй' ? '__pending__' : $key;
                $filterUrl = $decisionFilterBaseUrl
                    ? $decisionFilterBaseUrl . (str_contains($decisionFilterBaseUrl, '?') ? '&' : '?') . http_build_query(['notes_decision_status' => $filterValue])
                    : null;
            @endphp
            @if($filterUrl)
                <a href="{{ $filterUrl }}" class="block min-w-[11.5rem] rounded-xl border p-3 {{ $cls }} transition hover:ring-2 hover:ring-slate-300/70">
                    <div class="text-[11px] font-semibold leading-snug opacity-80">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format((int) ($decisionCounts[$key] ?? 0)) }}</div>
                </a>
            @else
                <div class="min-w-[11.5rem] rounded-xl border p-3 {{ $cls }}">
                    <div class="text-[11px] font-semibold leading-snug opacity-80">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format((int) ($decisionCounts[$key] ?? 0)) }}</div>
                </div>
            @endif
        @endforeach
        </div>
    </div>
</div>
