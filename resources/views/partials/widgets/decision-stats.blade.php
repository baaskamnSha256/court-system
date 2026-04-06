@php
    $decisionOptions = $decisionOptions ?? [];
    $decisionCounts = $decisionCounts ?? [];
    $decisionFilterBaseUrl = $decisionFilterBaseUrl ?? null;

    $palette = [
        'Хүлээгдэж буй' => 'border-slate-200 bg-slate-50/60 text-slate-800',
        'Шийдвэрлэсэн' => 'border-emerald-200 bg-emerald-50/40 text-emerald-800',
        'Хойшилсон' => 'border-amber-200 bg-amber-50/40 text-amber-800',
        'Завсарласан' => 'border-sky-200 bg-sky-50/40 text-sky-800',
        'Прокурорт буцаасан' => 'border-rose-200 bg-rose-50/40 text-rose-800',
        'Яллагдагчийг шүүхэд шилжүүлсэн' => 'border-indigo-200 bg-indigo-50/40 text-indigo-800',
        '60 хүртэлх хоногоор хойшлуулсан' => 'border-violet-200 bg-violet-50/40 text-violet-800',
    ];
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm">
    <div class="flex items-center justify-between gap-2 mb-2">
        <h3 class="text-sm font-semibold text-slate-800">Шүүх хуралдааны шийдвэр (жилээр)</h3>
        <span class="text-[11px] sm:text-xs font-medium text-slate-500 shrink-0">{{ ($today ?? now())->format('Y') }} он (01/01-ээс)</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-2">
        @foreach($decisionOptions as $key => $label)
            @php
                $cls = $palette[$key] ?? 'border-slate-200 bg-slate-50/40 text-slate-800';
                $filterValue = $key === 'Хүлээгдэж буй' ? '__pending__' : $key;
                $filterUrl = $decisionFilterBaseUrl
                    ? $decisionFilterBaseUrl . (str_contains($decisionFilterBaseUrl, '?') ? '&' : '?') . http_build_query(['notes_decision_status' => $filterValue])
                    : null;
            @endphp
            @if($filterUrl)
                <a href="{{ $filterUrl }}" class="block rounded-lg border px-2 py-2 sm:px-2.5 sm:py-2 {{ $cls }} hover:ring-2 hover:ring-slate-300/70 transition">
                    <div class="text-[10px] sm:text-[11px] font-semibold opacity-80 leading-snug line-clamp-2">{{ $label }}</div>
                    <div class="mt-0.5 text-lg sm:text-xl font-extrabold tabular-nums">{{ number_format((int)($decisionCounts[$key] ?? 0)) }}</div>
                </a>
            @else
                <div class="rounded-lg border px-2 py-2 sm:px-2.5 sm:py-2 {{ $cls }}">
                    <div class="text-[10px] sm:text-[11px] font-semibold opacity-80 leading-snug line-clamp-2">{{ $label }}</div>
                    <div class="mt-0.5 text-lg sm:text-xl font-extrabold tabular-nums">{{ number_format((int)($decisionCounts[$key] ?? 0)) }}</div>
                </div>
            @endif
        @endforeach
    </div>
</div>

