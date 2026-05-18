@php
    use App\Support\HearingSummaryDisplay;

    $decisionSummaryHearings = $decisionSummaryHearings ?? null;
    $decisionStatusFilterLabel = $decisionStatusFilterLabel ?? null;
    $dateFrom = $dateFrom ?? null;
    $dateTo = $dateTo ?? null;
    $matterNamesById = $matterNamesById ?? collect();
@endphp

@if($decisionSummaryHearings)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
            <div class="text-sm font-semibold text-slate-800">
                @if($decisionStatusFilterLabel)
                    Шийдвэр: {{ $decisionStatusFilterLabel }}
                @else
                    Хурлын жагсаалт
                    @if($dateFrom && $dateTo)
                        <span class="ml-1 font-normal text-slate-500">({{ $dateFrom }} – {{ $dateTo }})</span>
                    @endif
                @endif
                <span class="ml-1 font-normal text-slate-500">({{ number_format($decisionSummaryHearings->total()) }} хурал)</span>
            </div>
            @if($decisionStatusFilterLabel)
                @php
                    $clearQuery = array_filter(request()->except(['notes_decision_status', 'page']), fn ($v) => $v !== null && $v !== '');
                @endphp
                <a href="{{ route('admin.reports.index', $clearQuery) }}"
                   class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    Шүүлтүүр арилгах
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[72rem] text-sm table-fixed">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        @php
                            $thClass = 'px-3 py-3 text-center text-sm font-semibold text-slate-700 leading-snug whitespace-normal break-words [text-wrap:wrap] align-top';
                        @endphp
                        <th class="{{ $thClass }} w-[4.5rem]">Огноо</th>
                        <th class="{{ $thClass }} w-[3.5rem]">Цаг</th>
                        <th class="{{ $thClass }} w-[4rem]">Танхим</th>
                        <th class="{{ $thClass }} w-[5.5rem]">Хэргийн дугаар</th>
                        <th class="{{ $thClass }} w-[7rem]">Шүүх бүрэлдэхүүн болон шүүгчийн нэр</th>
                        <th class="{{ $thClass }} w-[5.5rem]">Улсын яллагч</th>
                        <th class="{{ $thClass }} w-[6rem]">Шүүгдэгчийн нэр</th>
                        <th class="{{ $thClass }} w-[5rem]">Зүйл анги</th>
                        <th class="{{ $thClass }} w-[6rem]">Өмгөөлөгчийн нэр</th>
                        <th class="{{ $thClass }} w-[4rem]">ТСАХ</th>
                        <th class="{{ $thClass }} w-[8.5rem]">Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч</th>
                        <th class="{{ $thClass }} w-[8rem]">Шийдвэрийн тойм</th>
                        <th class="{{ $thClass }} w-[7rem]">Шийдвэрлэсэн зүйл анги</th>
                        <th class="{{ $thClass }} w-[6.5rem]">Шүүх хуралдааны шийдвэр</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($decisionSummaryHearings as $hearing)
                        @php($row = HearingSummaryDisplay::row($hearing, $matterNamesById))
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap text-center align-top">{{ $row['date'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap text-center align-top tabular-nums">{{ $row['time'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap text-center align-top">{{ $row['courtroom'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 text-center align-top whitespace-normal break-words">{{ $row['case_no'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $row['judges'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $row['prosecutor'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $row['defendants'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($row['matter_categories']))
                                    @foreach($row['matter_categories'] as $categoryName)
                                        <div class="text-xs whitespace-nowrap">{{ $categoryName }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($row['lawyers']))
                                    @foreach($row['lawyers'] as $line)
                                        <div class="text-xs">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $row['preventive'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($row['other_parties']))
                                    @foreach($row['other_parties'] as $line)
                                        <div class="text-xs">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-pre-wrap break-words text-xs">{{ $row['decision_summary'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($row['decided_matter_lines']))
                                    @foreach($row['decided_matter_lines'] as $matterLine)
                                        <div class="text-xs">{{ $matterLine }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center align-top">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium leading-snug whitespace-normal break-words [text-wrap:wrap] {{ HearingSummaryDisplay::decisionStatusBadgeClass($row['decision_status_key']) }}">
                                    {{ $row['decision_status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-8 text-center text-slate-500">
                                Сонгосон хугацаанд хурал олдсонгүй.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($decisionSummaryHearings->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $decisionSummaryHearings->links() }}
            </div>
        @endif
    </div>
@endif
