@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Хурлын зар')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">{{ $listTitle ?? 'Хурлын зарууд' }}</h1>
        @if(($indexType ?? null) !== 'readonly')
            <a href="{{ $createUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition-colors shadow-sm">
                + {{ $createLabel ?? 'Хурлын зар оруулах' }}
            </a>
        @endif
    </div>

    {{-- Хайлт --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ $searchUrl ?? $createUrl }}" class="flex flex-wrap items-end gap-3">
            @if(request()->filled('notes_decision_status'))
                <input type="hidden" name="notes_decision_status" value="{{ request('notes_decision_status') }}">
            @endif
            @if(request()->filled('hearing_date_from'))
                <input type="hidden" name="hearing_date_from" value="{{ request('hearing_date_from') }}">
            @endif
            @if(request()->filled('hearing_date_to'))
                <input type="hidden" name="hearing_date_to" value="{{ request('hearing_date_to') }}">
            @endif
            <div class="min-w-[200px] flex-1">
                <label for="q" class="block text-xs font-medium text-slate-500 mb-1">Хэргийн дугаар, шүүгдэгч, танхим</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}"
                       placeholder="Хайх..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div>
                <label for="hearing_date" class="block text-xs font-medium text-slate-500 mb-1">Огноо</label>
                <input type="date" name="hearing_date" id="hearing_date" value="{{ request('hearing_date') }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div>
                <label for="courtroom" class="block text-xs font-medium text-slate-500 mb-1">Танхим</label>
                <select name="courtroom" id="courtroom" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500 min-w-[6rem]">
                    <option value="">— Бүгд —</option>
                    @foreach($courtrooms ?? [] as $cr)
                        <option value="{{ $cr }}" @selected(request('courtroom') === $cr)>{{ $cr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                    Хайх
                </button>
                <a href="{{ $searchUrl ?? $createUrl }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Цэвэрлэх
                </a>
            </div>
        </form>

        @if(request()->filled('notes_decision_status'))
            @php
                $decisionFilterLabel = request('notes_decision_status') === '__pending__'
                    ? 'Хүлээгдэж буй'
                    : request('notes_decision_status');
            @endphp
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 text-blue-800 border border-blue-200 px-3 py-1 text-xs font-medium">
                    Шийдвэр: {{ $decisionFilterLabel }}
                    <a href="{{ request()->fullUrlWithQuery(['notes_decision_status' => null]) }}"
                       class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-900"
                       title="Шийдвэрийн шүүлтүүр арилгах">×</a>
                </span>
            </div>
        @endif
    </div>

    @isset($hearingStateCounts)
        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Хурлын төлөвөөр зарлагдсан тоо</h2>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach($states ?? [] as $state)
                    <span class="inline-flex items-center gap-2 text-sm">
                        <span class="text-slate-600">{{ $state }}:</span>
                        <span class="font-semibold text-slate-900">{{ $hearingStateCounts[$state] ?? 0 }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endisset

    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-auto">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Огноо</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Цаг</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Танхим</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Хэргийн дугаар</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Шүүх бүрэлдэхүүн болон шүүгчийн нэр</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Улсын яллагч</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Шүүгдэгчийн нэр</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Зүйл анги</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Өмгөөлөгчийн нэр</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">ТСАХ</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-700 whitespace-normal">Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч</th>
                        @if(($indexType ?? null) !== 'readonly')
                            <th class="px-3 py-2.5 text-right font-semibold text-slate-700 whitespace-normal">Үйлдэл</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($hearings as $h)
                        @php
                            $dateStr = $h->hearing_date ? (is_object($h->hearing_date) ? $h->hearing_date->format('Y-m-d') : $h->hearing_date) : (optional($h->start_at)->format('Y-m-d') ?? '—');
                            $timeStr = optional($h->start_at)->format('H:i') ?? ($h->hour !== null && $h->minute !== null ? sprintf('%02d:%02d', $h->hour, $h->minute) : '—');
                            $judgesStr = $h->relationLoaded('judges') && $h->judges->isNotEmpty() ? $h->judges->pluck('name')->implode(', ') : ($h->judge_names_text ?? '—');
                            $prosecutorStr = ($h->relationLoaded('prosecutor') && $h->prosecutor) ? $h->prosecutor->name : '—';
                            $defendants = is_array($h->defendant_names) ? implode(', ', $h->defendant_names) : ($h->defendants ?? $h->defendant_names ?? '—');
                            $def = is_array($h->defendant_lawyers_text) ? $h->defendant_lawyers_text : (is_string($h->defendant_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->defendant_lawyers_text))) : []);
                            $victim = is_array($h->victim_lawyers_text) ? $h->victim_lawyers_text : (is_string($h->victim_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->victim_lawyers_text))) : []);
                            $victimRep = is_array($h->victim_legal_rep_lawyers_text) ? $h->victim_legal_rep_lawyers_text : (is_string($h->victim_legal_rep_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->victim_legal_rep_lawyers_text))) : []);
                            $civilPl = is_array($h->civil_plaintiff_lawyers) ? $h->civil_plaintiff_lawyers : (is_string($h->civil_plaintiff_lawyers) ? array_filter(array_map('trim', explode(',', $h->civil_plaintiff_lawyers))) : []);
                            $civilDef = is_array($h->civil_defendant_lawyers) ? $h->civil_defendant_lawyers : (is_string($h->civil_defendant_lawyers) ? array_filter(array_map('trim', explode(',', $h->civil_defendant_lawyers))) : []);
                            $lawyerLines = [];
                            if (count($def)) $lawyerLines[] = 'ШүӨм: ' . implode(', ', $def);
                            if (count($victim)) $lawyerLines[] = 'ХоӨм: ' . implode(', ', $victim);
                            if (count($victimRep)) $lawyerLines[] = 'ХХЁТ-ийн Өм: ' . implode(', ', $victimRep);
                            if (count($civilPl)) $lawyerLines[] = 'ИНӨм: ' . implode(', ', $civilPl);
                            if (count($civilDef)) $lawyerLines[] = 'ИХӨм: ' . implode(', ', $civilDef);
                            $preventive = is_array($h->preventive_measure) ? implode(', ', $h->preventive_measure) : ($h->preventive_measure ?? '—');
                            $matterCategories = $h->matterCategories()->pluck('name')->all();
                            $otherLines = [];
                            if (trim((string)($h->victim_name ?? '')) !== '') $otherLines[] = 'Хохирогч: ' . trim($h->victim_name);
                            if (trim((string)($h->victim_legal_rep ?? '')) !== '') $otherLines[] = 'ХХЁТ: ' . trim($h->victim_legal_rep);
                            if (trim((string)($h->witnesses ?? '')) !== '') $otherLines[] = 'Гэрч: ' . trim($h->witnesses);
                            if (trim((string)($h->experts ?? '')) !== '') $otherLines[] = 'Шинжээч: ' . trim($h->experts);
                            if (trim((string)($h->civil_plaintiff ?? '')) !== '') $otherLines[] = 'ИН: ' . trim($h->civil_plaintiff);
                            if (trim((string)($h->civil_defendant ?? '')) !== '') $otherLines[] = 'ИХ: ' . trim($h->civil_defendant);
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top">{{ $dateStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top">{{ $timeStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top">{{ $h->courtroom ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top">{{ $h->case_no ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $judgesStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $prosecutorStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <div>{{ $defendants ?: '—' }}</div>
                                @if(!empty($h->hearing_state) && $h->hearing_state !== 'Хэвийн')
                                    <div class="text-sm text-slate-700 font-medium whitespace-nowrap">({{ $h->hearing_state }})</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($matterCategories))
                                    @foreach($matterCategories as $mc)
                                        <div class="text-sm whitespace-nowrap">{{ $mc }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($lawyerLines))
                                    @foreach($lawyerLines as $line)
                                        <div class="text-sm">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $preventive }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($otherLines))
                                    @foreach($otherLines as $line)
                                        <div class="text-sm">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            @if(($indexType ?? null) !== 'readonly')
                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($indexType === 'admin')
                                            <a href="{{ route('admin.hearings.edit', $h) }}" title="Засварлах" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </a>
                                            <form action="{{ route('admin.hearings.destroy', $h) }}" method="POST" class="inline" onsubmit="return confirm('Энэ хурлын зарыг устгах уу?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Устгах" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-800 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            @if ((int)$h->created_by === (int)auth()->id())
                                                <a href="{{ route('secretary.hearings.edit', $h) }}" title="Засварлах" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </a>
                                                <form action="{{ route('secretary.hearings.destroy', $h) }}" method="POST" class="inline" onsubmit="return confirm('Энэ хурлын зарыг устгах уу?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Устгах" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-800 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-slate-300 text-sm">—</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/30">
            {{ $hearings->links() }}
        </div>
    </div>
</div>
@endsection
