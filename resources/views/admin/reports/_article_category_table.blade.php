@php
    $articleTableColumns = $articleTableColumns ?? \App\Services\Reports\ArticleCategoryMetricsAggregator::tableColumns();
    $articleRows = $articleRows ?? [];
    $columnCount = count($articleTableColumns) + 1;
@endphp
<div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
        <div class="text-sm font-semibold text-slate-800">Шийдвэрлэсэн зүйл анги</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs table-auto min-w-[2400px]">
            <thead>
                <tr class="bg-white border-b border-slate-200">
                    <th class="sticky left-0 z-10 bg-white px-3 py-2.5 text-left font-semibold text-slate-700 border-r border-slate-200 min-w-[8rem]">Зүйл анги</th>
                    @foreach($articleTableColumns as $column)
                        <th class="px-2 py-2.5 text-center font-semibold text-slate-700 whitespace-normal break-words min-w-[4.5rem] leading-snug">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($articleRows as $row)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                        <td class="sticky left-0 z-10 bg-white px-3 py-2 text-slate-800 font-medium border-r border-slate-100 whitespace-normal break-words">{{ $row['name'] }}</td>
                        @foreach($articleTableColumns as $column)
                            @php
                                $value = (int) ($row[$column['key']] ?? 0);
                            @endphp
                            <td class="px-2 py-2 text-center text-slate-700 tabular-nums whitespace-nowrap">
                                {{ $value === 0 ? '—' : number_format($value) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-4 py-6 text-center text-slate-500">Мэдээлэлгүй</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
