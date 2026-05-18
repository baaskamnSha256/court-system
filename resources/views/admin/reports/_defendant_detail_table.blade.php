@php
    $detailCols = $defendantDetailColumns ?? [];
    $detailColCount = max(count($detailCols), 1);
    $caseColKeys = array_flip($defendantDetailColumnGroups['case'] ?? []);
    $defendantColKeys = array_flip($defendantDetailColumnGroups['defendant'] ?? []);
@endphp
<div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
        <div class="text-sm font-semibold text-slate-800">Шүүгдэгчийн дэлгэрэнгүй тайлан</div>
        <div class="text-xs text-slate-500 mt-0.5">Нэг хэрэг (хурал), шүүгдэгч бүр rowspan-аар; зүйл анги, ялын мэдээлэл мөр бүрт.</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm table-auto min-w-[1700px] border-collapse">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-200">
                    @foreach($detailCols as $col)
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-700 whitespace-normal break-words border-r border-slate-200 last:border-r-0">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($defendantDetailNestedGroups ?? [] as $caseGroup)
                    @php
                        $caseRowOpen = true;
                        $caseRow = $caseGroup['case_row'] ?? [];
                    @endphp
                    @foreach($caseGroup['defendants'] ?? [] as $defGroup)
                        @php
                            $defRowOpen = true;
                            $defRow = $defGroup['row'] ?? [];
                        @endphp
                        @foreach($defGroup['matters'] ?? [] as $matter)
                            @php $matterRow = $matter['row'] ?? []; @endphp
                            <tr class="border-b border-slate-100 align-top hover:bg-slate-50/60">
                                @foreach($detailCols as $col)
                                    @php
                                        $colKey = $col['key'] ?? '';
                                        $isCaseCol = isset($caseColKeys[$colKey]);
                                        $isDefendantCol = isset($defendantColKeys[$colKey]);
                                    @endphp
                                    @if($isCaseCol)
                                        @if($caseRowOpen)
                                            @php
                                                $cell = $caseRow[$colKey] ?? '';
                                                $display = ($cell === '' || $cell === null) ? '—' : $cell;
                                            @endphp
                                            <td rowspan="{{ $caseGroup['rowspan_case'] }}"
                                                class="px-3 py-2.5 text-slate-700 min-w-[100px] max-w-xs whitespace-normal break-words align-top border-r border-slate-100 bg-white">
                                                {{ $display }}
                                            </td>
                                        @endif
                                    @elseif($isDefendantCol)
                                        @if($defRowOpen)
                                            @php
                                                $cell = $defRow[$colKey] ?? '';
                                                $display = ($cell === '' || $cell === null) ? '—' : $cell;
                                            @endphp
                                            <td rowspan="{{ $defGroup['rowspan'] }}"
                                                class="px-3 py-2.5 text-slate-700 min-w-[100px] max-w-xs whitespace-normal break-words align-top border-r border-slate-100 bg-white {{ $colKey === 'defendant_name' ? 'font-medium' : '' }}">
                                                {{ $display }}
                                            </td>
                                        @endif
                                    @else
                                        @php
                                            $cell = $matterRow[$colKey] ?? '';
                                            $display = ($cell === '' || $cell === null) ? '—' : $cell;
                                        @endphp
                                        <td class="px-3 py-2.5 text-slate-700 min-w-[100px] max-w-xs whitespace-normal break-words align-top border-r border-slate-100 last:border-r-0">
                                            {{ $display }}
                                        </td>
                                    @endif
                                @endforeach
                                @php
                                    $caseRowOpen = false;
                                    $defRowOpen = false;
                                @endphp
                            </tr>
                        @endforeach
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $detailColCount }}" class="px-4 py-6 text-center text-slate-500">Мэдээлэлгүй</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
