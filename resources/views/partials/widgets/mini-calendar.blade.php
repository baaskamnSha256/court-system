@php
    use Carbon\Carbon;

    $monthStart = $today->copy()->startOfMonth();
    $monthEnd   = $today->copy()->endOfMonth();
    $startDayIndex = ($monthStart->dayOfWeekIso); // 1..7
    $daysInMonth = $monthStart->daysInMonth;
    $counts = $hearingsCountByDay ?? [];
    $monthTotal = is_array($counts) ? array_sum($counts) : 0;
    $dashboardUrl = $dashboardUrl ?? null;
    $prevMonth = $monthStart->copy()->subMonth()->format('Y-m-d');
    $nextMonth = $monthStart->copy()->addMonth()->format('Y-m-d');
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 shadow-sm w-full max-w-full">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-semibold text-slate-800">Календарь</h3>
        <div class="flex items-center gap-1">
            @if($dashboardUrl)
                <a href="{{ $dashboardUrl }}?date={{ $prevMonth }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors" title="Өмнөх сар">‹</a>
                <a href="{{ $dashboardUrl }}?date={{ $nextMonth }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors" title="Ирэх сар">›</a>
            @endif
        </div>
    </div>
    <div class="text-sm font-medium text-slate-600 mb-3">
        {{ $monthStart->format('Y оны m сар') }}
        <span class="text-xs font-semibold text-slate-500">· {{ $monthTotal }} хурал</span>
    </div>

    <div class="grid grid-cols-7 gap-1.5 text-xs font-medium text-slate-500 mb-2">
        <div class="text-center">Да</div><div class="text-center">Мя</div><div class="text-center">Лх</div><div class="text-center">Пү</div><div class="text-center">Ба</div><div class="text-center">Бя</div><div class="text-center">Ня</div>
    </div>

    <div class="grid grid-cols-7 gap-1.5">
        @for($i=1; $i<$startDayIndex; $i++)
            <div class="h-8 rounded-lg bg-slate-100/50"></div>
        @endfor
        @for($d=1; $d<=$daysInMonth; $d++)
            @php
                $isSelectedDay = ($monthStart->format('Y-m') === $today->format('Y-m')) && ($d === (int)$today->format('d'));
                $count = $counts[$d] ?? 0;
                $dayUrl = $dashboardUrl ? $dashboardUrl . '?date=' . $monthStart->format('Y') . '-' . $monthStart->format('m') . '-' . sprintf('%02d', $d) : null;
            @endphp
            @if($dayUrl)
                <a href="{{ $dayUrl }}" class="min-h-[2rem] h-8 rounded-lg flex flex-col items-center justify-center py-0.5 text-sm font-medium no-underline hover:opacity-90 {{ $isSelectedDay ? 'bg-slate-800 text-white ring-2 ring-slate-300' : 'bg-white border border-slate-200 text-slate-700 hover:border-slate-300' }}">
                    <span class="leading-none">{{ $d }}</span>
                    @if($count > 0)
                        <span class="text-[10px] leading-none font-semibold {{ $isSelectedDay ? 'text-green-200' : 'text-green-600' }}">{{ $count }}</span>
                    @endif
                </a>
            @else
                <div class="min-h-[2rem] h-8 rounded-lg flex flex-col items-center justify-center py-0.5 text-sm font-medium {{ $isSelectedDay ? 'bg-slate-800 text-white ring-2 ring-slate-300' : 'bg-white border border-slate-200 text-slate-700' }}">
                    <span class="leading-none">{{ $d }}</span>
                    @if($count > 0)
                        <span class="text-[10px] leading-none font-semibold {{ $isSelectedDay ? 'text-green-200' : 'text-green-600' }}">{{ $count }}</span>
                    @endif
                </div>
            @endif
        @endfor
    </div>
</div>
