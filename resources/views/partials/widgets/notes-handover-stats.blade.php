@php
    $monthTotalHearings = (int) ($monthTotalHearings ?? 0);
    $monthIssuedHearings = (int) ($monthIssuedHearings ?? 0);
    $monthPendingHearings = (int) ($monthPendingHearings ?? max(0, $monthTotalHearings - $monthIssuedHearings));
    $notesHandoverFilterBaseUrl = $notesHandoverFilterBaseUrl ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-xs font-semibold text-slate-500">Нийт шүүх хуралдаан</div>
        <div class="mt-2 text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($monthTotalHearings) }}</div>
        <div class="mt-1 text-xs text-slate-500">Сонгосон сарын дүн</div>
    </div>

    @php
        $issuedUrl = $notesHandoverFilterBaseUrl
            ? $notesHandoverFilterBaseUrl . (str_contains($notesHandoverFilterBaseUrl, '?') ? '&' : '?') . http_build_query(['notes_handover_issued' => 1])
            : null;
        $pendingUrl = $notesHandoverFilterBaseUrl
            ? $notesHandoverFilterBaseUrl . (str_contains($notesHandoverFilterBaseUrl, '?') ? '&' : '?') . http_build_query(['notes_handover_issued' => 0])
            : null;
    @endphp

    @if($issuedUrl)
        <a href="{{ $issuedUrl }}" class="block rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm hover:ring-2 hover:ring-emerald-300/50 transition">
            <div class="text-xs font-semibold text-emerald-700">Тэмдэглэл хүлээлцсэн</div>
            <div class="mt-2 text-2xl font-bold text-emerald-800 tabular-nums">{{ number_format($monthIssuedHearings) }}</div>
            <div class="mt-1 text-xs text-emerald-700/80">Админ/Шүүгчийн туслах баталгаажуулсан</div>
        </a>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm">
            <div class="text-xs font-semibold text-emerald-700">Тэмдэглэл хүлээлцсэн</div>
            <div class="mt-2 text-2xl font-bold text-emerald-800 tabular-nums">{{ number_format($monthIssuedHearings) }}</div>
            <div class="mt-1 text-xs text-emerald-700/80">Админ/Шүүгчийн туслах баталгаажуулсан</div>
        </div>
    @endif

    @if($pendingUrl)
        <a href="{{ $pendingUrl }}" class="block rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm hover:ring-2 hover:ring-amber-300/50 transition">
            <div class="text-xs font-semibold text-amber-700">Тэмдэглэл хүлээлцээгүй</div>
            <div class="mt-2 text-2xl font-bold text-amber-800 tabular-nums">{{ number_format($monthPendingHearings) }}</div>
            <div class="mt-1 text-xs text-amber-700/80">Хүлээлцэх шаардлагатай</div>
        </a>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm">
            <div class="text-xs font-semibold text-amber-700">Тэмдэглэл хүлээлцээгүй</div>
            <div class="mt-2 text-2xl font-bold text-amber-800 tabular-nums">{{ number_format($monthPendingHearings) }}</div>
            <div class="mt-1 text-xs text-amber-700/80">Хүлээлцэх шаардлагатай</div>
        </div>
    @endif
</div>

