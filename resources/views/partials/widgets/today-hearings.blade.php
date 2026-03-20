@php
    use Carbon\Carbon;
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 shadow-sm flex flex-col min-h-0">
    <div class="flex items-center justify-between mb-3 shrink-0">
        <h3 class="font-semibold text-slate-800">{{ $today->isToday() ? 'Өнөөдрийн хурал' : $today->format('Y-m-d') . '-ийн хурал' }}</h3>
        <span class="text-xs font-medium text-slate-500">{{ $today->format('Y-m-d') }} · {{ $hearingsToday->count() }} хурал</span>
    </div>

    @if($hearingsToday->isEmpty())
        <p class="text-sm text-slate-500">{{ $today->isToday() ? 'Өнөөдөр зарлагдсан хурал алга.' : 'Тухайн өдөрт зарлагдсан хурал алга.' }}</p>
    @else
        <div class="border border-slate-200 rounded-xl bg-white overflow-hidden flex-1 min-h-0 flex flex-col">
            <div class="overflow-auto" style="max-height: min(70vh, 600px);">
                <table class="w-full text-sm border-collapse">
                    <thead class="sticky top-0 bg-slate-100 z-10 border-b border-slate-200">
                        <tr>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap w-9">№</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[90px]">Хэргийн дугаар</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[80px]">Огноо</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap w-14">Цаг</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap w-16">Танхим</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[70px]">Төлөв</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[100px]">Шүүгч</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[100px]">Шүүгдэгч</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[100px]">Өмгөөлөгч</th>
                            <th class="text-left py-2 px-2 font-semibold text-slate-700 whitespace-nowrap min-w-[80px]">Прокурор</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hearingsToday as $index => $h)
                            @php
                                $startsAt = $h->start_at ? Carbon::parse($h->start_at) : null;
                                $dateStr = $h->hearing_date
                                    ? (is_object($h->hearing_date) ? $h->hearing_date->format('Y-m-d') : (string) $h->hearing_date)
                                    : ($startsAt ? $startsAt->format('Y-m-d') : '—');
                                $timeStr = $startsAt
                                    ? $startsAt->format('H:i')
                                    : (($h->hour !== null && $h->minute !== null) ? sprintf('%02d:%02d', $h->hour, $h->minute) : '—');
                                $judgesStr = $h->relationLoaded('judges') && $h->judges->isNotEmpty()
                                    ? $h->judges->pluck('name')->implode(', ')
                                    : ($h->judge_names_text ?? '');
                                $judgesStr = trim($judgesStr) !== '' ? $judgesStr : '—';
                                $defendantsStr = is_array($h->defendant_names) ? implode(', ', $h->defendant_names) : ($h->defendant_name ?? $h->defendant_names ?? '');
                                $defendantsStr = trim($defendantsStr) !== '' ? $defendantsStr : '—';
                                $lawyersStr = '—';
                                if (!empty($h->defendant_lawyers_text) && is_array($h->defendant_lawyers_text)) {
                                    $lawyersStr = implode(', ', $h->defendant_lawyers_text);
                                } elseif (!empty($h->defendant_lawyers) && is_array($h->defendant_lawyers)) {
                                    $lawyersStr = is_array($h->defendant_lawyers[0] ?? null) ? implode(', ', array_column($h->defendant_lawyers, 'name')) : implode(', ', $h->defendant_lawyers);
                                } elseif (!empty($h->lawyer_name)) {
                                    $lawyersStr = $h->lawyer_name;
                                }
                                $lawyersStr = trim($lawyersStr) !== '' ? $lawyersStr : '—';
                                $prosecutorStr = $h->relationLoaded('prosecutor') && $h->prosecutor ? $h->prosecutor->name : ($h->prosecutor_name ?? '');
                                $prosecutorStr = trim($prosecutorStr) !== '' ? $prosecutorStr : '—';
                                $caseNo = trim($h->case_no ?? '') !== '' ? $h->case_no : '—';
                            @endphp
                            <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                                <td class="py-1.5 px-2 text-slate-500 font-medium">{{ $index + 1 }}</td>
                                <td class="py-1.5 px-2 text-slate-800 whitespace-nowrap">{{ $caseNo }}</td>
                                <td class="py-1.5 px-2 text-slate-800 whitespace-nowrap">{{ $dateStr }}</td>
                                <td class="py-1.5 px-2 text-slate-800 whitespace-nowrap">{{ $timeStr }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $h->courtroom ?? '—' }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $h->hearing_state ?? $h->status ?? '—' }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $judgesStr }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $defendantsStr }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $lawyersStr }}</td>
                                <td class="py-1.5 px-2 text-slate-800">{{ $prosecutorStr }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
