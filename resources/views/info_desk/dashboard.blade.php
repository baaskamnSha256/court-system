@extends('layouts.dashboard')
@section('title', 'Мэдээлэл лавлагаа')
@section('header', '')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Хайлт (огнооны интервал) --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ $searchUrl }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date_from" class="block text-xs font-medium text-slate-500 mb-1">Эхлэх огноо</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-medium text-slate-500 mb-1">Дуусах огноо</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                    Хайх
                </button>
                <a href="{{ $searchUrl }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Цэвэрлэх
                </a>
                <a href="{{ $downloadUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 transition-colors">
                    Excel татах
                </a>
            </div>
        </form>
        @if(!empty($exportLimit))
            <div class="mt-2 text-xs text-slate-500">
                Excel таталт: хамгийн ихдээ {{ $exportLimit }} зар.
            </div>
        @endif
    </div>

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
                            if (count($victimRep)) $lawyerLines[] = 'ХХЁТӨм: ' . implode(', ', $victimRep);
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
