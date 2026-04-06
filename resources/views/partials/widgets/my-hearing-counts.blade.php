<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
        <div class="text-xs font-semibold text-slate-600">Миний хурал (сар)</div>
        <div class="mt-1 text-2xl font-bold text-slate-800 tabular-nums">{{ number_format((int) ($monthTotalHearings ?? 0)) }}</div>
    </div>
    <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 px-4 py-3">
        <div class="text-xs font-semibold text-indigo-700">Миний хурал (өнөөдөр)</div>
        <div class="mt-1 text-2xl font-bold text-indigo-800 tabular-nums">{{ number_format((int) ($todayHearingsCount ?? 0)) }}</div>
    </div>
</div>
