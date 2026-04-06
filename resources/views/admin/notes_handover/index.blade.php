@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Тэмдэглэл хүлээлцэх')

@section('content')
<div class="space-y-6">
    <h1 class="text-lg font-semibold text-slate-800">Тэмдэглэл хүлээлцэх</h1>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Хайлт --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @php
            $notesPrefix = $notesRoutePrefix ?? 'admin';
            $isClerkUser = $notesPrefix === 'court_clerk';
            $currentClerkName = auth()->user()?->name;
        @endphp
        <form method="GET" action="{{ route($notesPrefix . '.notes.index') }}" class="flex flex-wrap items-end gap-3">
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
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                    Хайх
                </button>
                <a href="{{ route($notesPrefix . '.notes.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Цэвэрлэх
                </a>
            </div>
        </form>
    </div>

    {{-- Жагсаалт --}}
    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-auto min-w-max">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">№</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Огноо</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Цаг</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Танхим</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шүүх бүрэлдэхүүн болон шүүгчийн нэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шүүгдэгчийн нэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">ТСАХ</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Зүйл анги</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Улсын яллагч</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Өмгөөлөгчийн нэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шүүх хуралдаан хойшилсон тойм</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm min-w-[280px]">Шийдвэрлэсэн зүйл анги</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Торгох нэгж</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Хохирлын дүн</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шүүх хуралдааны шийдвэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">ШХНБ дарга</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">ШХНБ дарга сонгосон цаг</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Тэмдэглэл гаргасан эсэх</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Бүртгэсэн цаг</th>
                        <th class="sticky right-0 z-20 bg-slate-50 px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.35)]">Үйлдэл</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hearings as $h)
                        @php
                            $dateStr = $h->hearing_date ? (is_object($h->hearing_date) ? $h->hearing_date->format('Y-m-d') : $h->hearing_date) : (optional($h->start_at)->format('Y-m-d') ?? '—');
                            $timeStr = optional($h->start_at)->format('H:i') ?? ($h->hour !== null && $h->minute !== null ? sprintf('%02d:%02d', $h->hour, $h->minute) : '—');
                            $judgesStr = $h->relationLoaded('judges') && $h->judges->isNotEmpty() ? $h->judges->pluck('name')->implode(', ') : ($h->judge_names_text ?? '—');
                            $prosecutorStr = ($h->relationLoaded('prosecutor') && $h->prosecutor) ? $h->prosecutor->name : ($h->prosecutor_name ?? '—');
                            $defendants = is_array($h->defendant_names) ? implode(', ', $h->defendant_names) : ($h->defendants ?? $h->defendant_names ?? '—');
                            $defendantNamesList = is_array($h->defendant_names)
                                ? collect($h->defendant_names)->map(fn ($name) => trim((string) $name))->filter()->values()->all()
                                : collect(preg_split('/[\n,]+/u', (string) ($h->defendants ?? $h->defendant_names ?? '')))
                                    ->map(fn ($name) => trim((string) $name))
                                    ->filter()
                                    ->values()
                                    ->all();
                            $storedDefendantSentences = is_array($h->notes_defendant_sentences) ? $h->notes_defendant_sentences : [];
                            $storedSentencesByName = collect($storedDefendantSentences)
                                ->filter(fn ($sentence) => is_array($sentence))
                                ->keyBy(fn ($sentence) => trim((string) ($sentence['defendant_name'] ?? '')))
                                ->all();
                            $preventive = is_array($h->preventive_measure) ? implode(', ', $h->preventive_measure) : ($h->preventive_measure ?? '—');
                            $matterCategories = $h->matterCategories()->pluck('name')->all();

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

                            $otherLines = [];
                            if (trim((string)($h->victim_name ?? '')) !== '') $otherLines[] = 'Хохирогч: ' . trim($h->victim_name);
                            if (trim((string)($h->victim_legal_rep ?? '')) !== '') $otherLines[] = 'ХХЁТ: ' . trim($h->victim_legal_rep);

                            if (trim((string)($h->witnesses ?? '')) !== '') $otherLines[] = 'Гэрч: ' . trim($h->witnesses);
                            if (trim((string)($h->experts ?? '')) !== '') $otherLines[] = 'Шинжээч: ' . trim($h->experts);
                            if (trim((string)($h->civil_plaintiff ?? '')) !== '') $otherLines[] = 'ИН: ' . trim($h->civil_plaintiff);
                            if (trim((string)($h->civil_defendant ?? '')) !== '') $otherLines[] = 'ИХ: ' . trim($h->civil_defendant);
                            $decidedMatterSelectedIds = [];
                            if (!empty($h->notes_decided_matter ?? '') && isset($allMatterCategories)) {
                                $tokens = array_map('trim', preg_split('/[,;\n]+/u', $h->notes_decided_matter));
                                $tokens = array_filter($tokens, fn($t) => $t !== '');
                                if (!empty($tokens)) {
                                    $decidedMatterSelectedIds = $allMatterCategories
                                        ->whereIn('name', $tokens)
                                        ->pluck('id')
                                        ->all();
                                }
                            }
                            $originMatterNames = collect($matterCategories)->map(fn ($name) => trim((string) $name))->filter()->unique()->values();
                            $decidedMatterNames = collect(preg_split('/[,;\n]+/u', (string) ($h->notes_decided_matter ?? '')))
                                ->map(fn ($name) => trim((string) $name))
                                ->filter()
                                ->unique()
                                ->values();
                            $hasDecidedMatterChange = $decidedMatterNames->isNotEmpty()
                                && ($originMatterNames->sort()->values()->all() !== $decidedMatterNames->sort()->values()->all());

                            $decisionStatus = (string) ($h->notes_decision_status ?? '');
                            $decisionCellClass = match ($decisionStatus) {
                                'Шийдвэрлэсэн' => 'bg-emerald-50/70',
                                'Хойшилсон' => 'bg-amber-50/70',
                                'Завсарласан' => 'bg-sky-50/70',
                                'Прокурорт буцаасан' => 'bg-rose-50/70',
                                'Яллагдагчийг шүүхэд шилжүүлсэн' => 'bg-indigo-50/70',
                                '60 хүртэлх хоногоор хойшлуулсан' => 'bg-violet-50/70',
                                default => 'bg-slate-50/70',
                            };

                            $decisionBadgeClass = match ($decisionStatus) {
                                'Шийдвэрлэсэн' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Хойшилсон' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'Завсарласан' => 'bg-sky-100 text-sky-800 border-sky-200',
                                'Прокурорт буцаасан' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'Яллагдагчийг шүүхэд шилжүүлсэн' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                '60 хүртэлх хоногоор хойшлуулсан' => 'bg-violet-100 text-violet-800 border-violet-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };

                            $issuedCellClass = $h->notes_handover_issued ? 'bg-emerald-50/60' : 'bg-amber-50/60';
                            $formatGroupedNumber = function ($value): string {
                                $raw = trim((string) $value);
                                if ($raw === '') {
                                    return '';
                                }
                                $digits = preg_replace('/\D+/', '', $raw);
                                if ($digits === '' || $digits === null) {
                                    return $raw;
                                }

                                return number_format((int) $digits, 0, '.', ',');
                            };
                            $notesFineUnitsFormatted = $formatGroupedNumber(old('notes_fine_units', $h->notes_fine_units));
                            $notesDamageAmountFormatted = $formatGroupedNumber(old('notes_damage_amount', $h->notes_damage_amount));
                        @endphp
                        @php
                            $formId = 'notes-form-' . $h->id;
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                            x-data="{ openModal: false, decisionStatus: @js(old('notes_decision_status', $h->notes_decision_status ?? '')), formatGroupedValue(rawValue) { if (window.formatGroupedNumber) { return window.formatGroupedNumber(rawValue); } const raw = String(rawValue || ''); const digits = raw.replace(/[^0-9]+/g, '').replace(/^0+(?=[0-9])/, ''); return digits.replace(/([0-9])(?=([0-9]{3})+$)/g, '$1,'); }, formatGroupedInput(event) { const input = event?.target; if (!input) return; input.value = this.formatGroupedValue(input.value); }, cancel() { this.openModal = false; document.getElementById('{{ $formId }}')?.reset(); } }">
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top text-center">
                                {{ ($hearings->currentPage() - 1) * $hearings->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top text-center">{{ $dateStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top text-center">{{ $timeStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap align-top text-center">{{ $h->courtroom ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $judgesStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words min-w-[180px]">
                                {{ $defendants }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $preventive }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($matterCategories))
                                    @foreach($matterCategories as $mc)
                                        <div class="text-xs whitespace-nowrap">{{ $mc }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">{{ $prosecutorStr }}</td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($lawyerLines))
                                    @foreach($lawyerLines as $line)
                                        <div class="text-xs">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                @if(count($otherLines))
                                    @foreach($otherLines as $line)
                                        <div class="text-xs">{{ $line }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <textarea name="notes_handover_text" rows="3" disabled form="{{ $formId }}"
                                          class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">{{ old('notes_handover_text', $h->notes_handover_text) }}</textarea>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words min-w-[300px]">
                                <div class="text-xs text-slate-700 whitespace-normal break-words">
                                    {{ $h->notes_decided_matter ?: '—' }}
                                </div>
                                <div class="mt-1">
                                    @if($h->notes_decided_matter)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $hasDecidedMatterChange ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                            {{ $hasDecidedMatterChange ? 'Анхны зүйл ангиас өөрчлөгдсөн' : 'Анхны зүйл ангитай ижил' }}
                                        </span>
                                    @endif
                                </div>
                                @php
                                    $chipOptions = ($allMatterCategories ?? collect())
                                        ->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name])
                                        ->values();
                                    $originChipSelected = ($allMatterCategories ?? collect())
                                        ->whereIn('name', $matterCategories)
                                        ->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name])
                                        ->values();
                                    $chipSelected = collect(old('notes_decided_matter_ids', $decidedMatterSelectedIds))
                                        ->map(function ($id) use ($allMatterCategories) {
                                            $id = (int) $id;
                                            $name = ($allMatterCategories ?? collect())->firstWhere('id', $id)?->name ?? '';
                                            return ['id' => (string) $id, 'name' => $name];
                                        })
                                        ->filter(fn($s) => ($s['name'] ?? '') !== '')
                                        ->values();
                                @endphp

                                <div
                                     x-show="false"
                                     x-cloak
                                     x-data="(() => {
                                         const state = chipSelect({
                                             options: @js($chipOptions),
                                             selected: @js($chipSelected),
                                             single: false,
                                             placeholder: 'Зүйл анги хайх...',
                                             nameId: 'notes_decided_matter_ids[]'
                                         });
                                         state.originSelected = @js($originChipSelected);
                                         return state;
                                     })()"
                                     x-init="init()"
                                     class="mt-0.5">
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <div class="text-[11px] text-slate-500">
                                            Анхны зүйл анги: {{ count($matterCategories) ? implode(', ', $matterCategories) : '—' }}
                                        </div>
                                        @if(count($matterCategories))
                                            <button type="button"
                                                    @click="selected = [...originSelected]; query = ''; open = false; refreshFiltered()"
                                                    class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                                Анхныхыг ашиглах
                                            </button>
                                        @endif
                                    </div>
                                    <div class="rounded-lg border border-slate-300 bg-white focus-within:ring-1 focus-within:ring-sky-300 focus-within:border-sky-500"
                                         @click="openNow(); $refs.input?.focus()"
                                         @click.outside="open = false"
                                    >
                                        <div class="flex flex-wrap items-center gap-1.5 px-2 py-1.5 min-h-[2.25rem]">
                                            <template x-for="s in selected" :key="'c-'+s.id">
                                                <span class="inline-flex items-center gap-1 bg-slate-100 border border-slate-300 rounded px-2 py-0.5 text-xs">
                                                    <span x-text="s.name"></span>
                                                    <button type="button" @click.stop="remove(s)" class="text-slate-500 hover:text-red-600 leading-none">&times;</button>
                                                </span>
                                            </template>
                                            <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off"
                                                   disabled
                                                   @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                                                   placeholder="Зүйл анги хайх..."
                                                   class="flex-1 min-w-[8rem] border-0 py-1 text-xs focus:ring-0 focus:outline-none disabled:bg-transparent disabled:text-slate-400 disabled:cursor-not-allowed">
                                        </div>
                                        <div x-show="open" x-cloak class="border-t border-slate-200 max-h-48 overflow-auto">
                                            <template x-for="opt in filteredOptions" :key="opt.id">
                                                <div @click="toggle(opt)"
                                                     class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-slate-50 text-xs border-b border-slate-50 last:border-0">
                                                    <span x-text="opt.name"></span>
                                                    <span x-show="isSelected(opt)" class="text-slate-800 font-bold">✓</span>
                                                </div>
                                            </template>
                                            <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-slate-500 text-xs">Олдсонгүй</div>
                                        </div>
                                    </div>

                                    <template x-for="s in selected" :key="'h-'+s.id">
                                        <input type="hidden" name="notes_decided_matter_ids[]" :value="s.id" form="{{ $formId }}">
                                    </template>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <input type="text" name="notes_fine_units"
                                       value="{{ $notesFineUnitsFormatted }}"
                                       disabled form="{{ $formId }}"
                                       class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <input type="text" name="notes_damage_amount"
                                       value="{{ $notesDamageAmountFormatted }}"
                                       disabled form="{{ $formId }}"
                                       class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words {{ $decisionCellClass }}">
                                @php
                                    $decisionOptions = [
                                        '' => '— Сонгох —',
                                        'Шийдвэрлэсэн' => 'Шийдвэрлэсэн',
                                        'Хойшилсон' => 'Хойшилсон',
                                        'Завсарласан' => 'Завсарласан',
                                        'Прокурорт буцаасан' => 'Прокурорт буцаасан',
                                        'Яллагдагчийг шүүхэд шилжүүлсэн' => 'Яллагдагчийг шүүхэд шилжүүлсэн',
                                        '60 хүртэлх хоногоор хойшлуулсан' => '60 хүртэлх хоногоор хойшлуулсан',
                                    ];
                                @endphp
                                <div class="mb-1">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $decisionBadgeClass }}">
                                        {{ $h->notes_decision_status ?: 'Хүлээгдэж буй' }}
                                    </span>
                                </div>
                                <select name="notes_decision_status"
                                        disabled form="{{ $formId }}" required
                                        class="w-full min-w-[140px] rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                                    @foreach($decisionOptions as $val => $label)
                                        <option value="{{ $val }}" @selected(old('notes_decision_status', $h->notes_decision_status) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words min-w-[200px]">
                                @if($isClerkUser)
                                    <div class="text-xs text-slate-700">
                                        {{ $currentClerkName ?? '—' }}
                                    </div>
                                @else
                                    <select name="clerk_id" disabled required
                                            form="{{ $formId }}"
                                            class="w-full min-w-[140px] rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                                        <option value="">— Сонгоогүй —</option>
                                        @foreach($clerks as $clerk)
                                            <option value="{{ $clerk->id }}" @selected(old('clerk_id', $h->clerk_id) == $clerk->id)>{{ $clerk->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center text-xs">
                                {{ optional($h->notes_clerk_selected_at)->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center {{ $issuedCellClass }}">
                                <div class="flex flex-col items-center gap-1 text-xs">
                                    <label class="inline-flex items-center gap-1 text-slate-700">
                                        @if(!$isClerkUser)
                                            <input type="checkbox" name="notes_handover_issued" value="1"
                                                   disabled
                                                   form="{{ $formId }}"
                                                   class="rounded border-slate-300 disabled:cursor-not-allowed"
                                                   @checked(old('notes_
                                                   handover_issued', $h->notes_handover_issued))>
                                            Тэмдэглэл гаргасан
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </label>
                                    <div>
                                        @if($h->notes_handover_issued)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium">
                                                Гаргасан
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 text-[11px] font-medium">
                                                Гаргаагүй
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center text-xs">
                                {{ optional($h->notes_handover_saved_at)->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="sticky right-0 z-10 bg-white px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.25)]">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" @click="openModal = true"
                                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Засварлах
                                    </button>
                                </div>
                                <template x-teleport="body">
                                    <div x-show="openModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/65 backdrop-blur-[1px] p-4">
                                        <div @click.outside="cancel()" class="w-full max-w-4xl rounded-xl bg-white shadow-2xl max-h-[85vh] overflow-hidden">
                                            <div class="grid grid-cols-[1fr_auto_1fr] items-center border-b border-slate-200 px-4 py-3">
                                                <h3 class="col-start-2 text-center text-sm font-semibold text-slate-800">Шүүх хуралдааны тойм засварлах</h3>
                                                <button type="button" @click="cancel()" class="col-start-3 justify-self-end rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Хаах</button>
                                            </div>
                                            <div class="overflow-y-auto px-4 py-3 max-h-[calc(85vh-8.5rem)]">
                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-center text-xs font-medium text-slate-600">Шүүх хуралдааны тойм</label>
                                                <textarea name="notes_handover_text" rows="3" form="{{ $formId }}" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">{{ old('notes_handover_text', $h->notes_handover_text) }}</textarea>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-center text-xs font-medium text-slate-600">Шүүх хуралдааны шийдвэр</label>
                                                <select name="notes_decision_status" x-model="decisionStatus" form="{{ $formId }}" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                                                    @foreach($decisionOptions as $val => $label)
                                                        <option value="{{ $val }}" @selected(old('notes_decision_status', $h->notes_decision_status) === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if(!$isClerkUser)
                                                <div>
                                                    <label class="mb-1 block text-center text-xs font-medium text-slate-600">ШХНБ дарга</label>
                                                    <select name="clerk_id" form="{{ $formId }}" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                                                        <option value="">— Сонгоогүй —</option>
                                                        @foreach($clerks as $clerk)
                                                            <option value="{{ $clerk->id }}" @selected(old('clerk_id', $h->clerk_id) == $clerk->id)>{{ $clerk->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                            <div class="md:col-span-2" x-show="decisionStatus === 'Шийдвэрлэсэн'">
                                                <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                                    <h4 class="mb-2 text-xs font-semibold text-slate-700">Шүүгдэгч тус бүрийн шийтгэлийн мэдээлэл</h4>
                                                    <div class="space-y-3">
                                                        @foreach($defendantNamesList as $defendantIndex => $defendantName)
                                                            @php
                                                                $sentencePrefill = $storedSentencesByName[trim((string) $defendantName)] ?? [];
                                                                $allocationsPrefill = is_array($sentencePrefill['allocations'] ?? null) ? $sentencePrefill['allocations'] : [];
                                                            @endphp
                                                            <div class="rounded-lg border border-slate-200 bg-white p-3" x-data="{ rowsOpen: true, allocRows: @js(array_values($allocationsPrefill)), addAllocRow() { this.allocRows.push({ matter_category_id: '', punishments: { fine: { fine_units: '', damage_amount: '' }, community_service: { hours: '' }, travel_restriction: { years: '', months: '' }, imprisonment_open: { years: '', months: '' }, imprisonment_closed: { years: '', months: '' }, rights_ban_public_service: { years: '', months: '' }, rights_ban_professional_activity: { years: '', months: '' }, rights_ban_driving: { years: '', months: '' } } }); }, removeAllocRow(index) { this.allocRows.splice(index, 1); } }">
                                                                <input type="hidden" name="notes_defendant_sentences[{{ $defendantIndex }}][defendant_name]" value="{{ $defendantName }}" form="{{ $formId }}">
                                                                <div class="mb-2 text-xs font-semibold text-slate-700">{{ $defendantName }}</div>
                                                                <div class="mt-3 rounded border border-indigo-200 bg-indigo-50/40 p-2">
                                                                    <div class="mb-2 flex items-center justify-between gap-2">
                                                                        <label class="text-xs font-semibold text-indigo-800">Зүйл анги тус бүрт ялын хуваарилалт (С хувилбар)</label>
                                                                        <div class="flex items-center gap-2">
                                                                            <button type="button" @click="rowsOpen = !rowsOpen" class="inline-flex items-center rounded border border-slate-300 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50" x-text="rowsOpen ? 'Хаах' : 'Нээх'"></button>
                                                                            <button type="button" @click="addAllocRow()" class="inline-flex items-center rounded border border-indigo-300 bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 hover:bg-indigo-50">+ Мөр нэмэх</button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="space-y-2">
                                                                        <template x-for="(row, allocIndex) in allocRows" :key="'alloc-'+allocIndex">
                                                                            <div class="rounded border border-indigo-100 bg-white p-2" x-data="{ afine: false, acommunity: false, atravel: false, aimprOpen: false, aimprClosed: false, arightsPublic: false, arightsPro: false, arightsDrive: false }">
                                                                                <div class="mb-2 flex items-center justify-between gap-2">
                                                                                    <select :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][matter_category_id]`" x-model="row.matter_category_id" form="{{ $formId }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                        <option value="">— Зүйл анги сонгох —</option>
                                                                                        @foreach(($allMatterCategories ?? collect()) as $category)
                                                                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                    <button type="button" @click="removeAllocRow(allocIndex)" class="shrink-0 rounded border border-rose-200 px-2 py-1 text-[11px] text-rose-700 hover:bg-rose-50">Устгах</button>
                                                                                </div>
                                                                                <div x-show="rowsOpen" class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                                    <div class="rounded border border-slate-200 p-2">
                                                                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                                                                            <input type="checkbox" x-model="afine" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][fine][enabled]`" value="1" form="{{ $formId }}">Торгох
                                                                                        </label>
                                                                                        <div x-show="afine" class="mt-2 grid grid-cols-2 gap-2">
                                                                                            <input type="text" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][fine][fine_units]`" x-model="row.punishments.fine.fine_units" @input="formatGroupedInput($event)" placeholder="Торгох нэгж" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                            <input type="text" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][fine][damage_amount]`" x-model="row.punishments.fine.damage_amount" @input="formatGroupedInput($event)" placeholder="Хохирлын дүн" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="rounded border border-slate-200 p-2">
                                                                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                                                                            <input type="checkbox" x-model="acommunity" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][community_service][enabled]`" value="1" form="{{ $formId }}">Нийтэд тустай ажил
                                                                                        </label>
                                                                                        <div x-show="acommunity" class="mt-2">
                                                                                            <input type="number" min="0" max="720" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][community_service][hours]`" x-model="row.punishments.community_service.hours" placeholder="Цаг (max 720)" form="{{ $formId }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="rounded border border-slate-200 p-2">
                                                                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                                                                            <input type="checkbox" x-model="atravel" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][travel_restriction][enabled]`" value="1" form="{{ $formId }}">Зорчих эрх
                                                                                        </label>
                                                                                        <div x-show="atravel" class="mt-2 grid grid-cols-2 gap-2">
                                                                                            <input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][travel_restriction][years]`" x-model="row.punishments.travel_restriction.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                            <input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][travel_restriction][months]`" x-model="row.punishments.travel_restriction.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="rounded border border-slate-200 p-2">
                                                                                        <div class="text-xs font-medium text-slate-700">Хорих ял</div>
                                                                                        <div class="mt-2 space-y-2">
                                                                                            <label class="inline-flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="aimprOpen" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_open][enabled]`" value="1" form="{{ $formId }}">Нээлттэй</label>
                                                                                            <div x-show="aimprOpen" class="grid grid-cols-2 gap-2"><input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_open][years]`" x-model="row.punishments.imprisonment_open.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_open][months]`" x-model="row.punishments.imprisonment_open.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div>
                                                                                            <label class="inline-flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="aimprClosed" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_closed][enabled]`" value="1" form="{{ $formId }}">Хаалттай</label>
                                                                                            <div x-show="aimprClosed" class="grid grid-cols-2 gap-2"><input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_closed][years]`" x-model="row.punishments.imprisonment_closed.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][imprisonment_closed][months]`" x-model="row.punishments.imprisonment_closed.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="rounded border border-slate-200 p-2 md:col-span-2">
                                                                                        <div class="text-xs font-medium text-slate-700">Эрх хасах</div>
                                                                                        <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-3">
                                                                                            <div><label class="inline-flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="arightsPublic" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_public_service][enabled]`" value="1" form="{{ $formId }}">Нийтийн алба</label><div x-show="arightsPublic" class="mt-1 grid grid-cols-2 gap-1"><input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_public_service][years]`" x-model="row.punishments.rights_ban_public_service.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_public_service][months]`" x-model="row.punishments.rights_ban_public_service.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                                            <div><label class="inline-flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="arightsPro" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_professional_activity][enabled]`" value="1" form="{{ $formId }}">Мэргэжлийн эрх</label><div x-show="arightsPro" class="mt-1 grid grid-cols-2 gap-1"><input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_professional_activity][years]`" x-model="row.punishments.rights_ban_professional_activity.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_professional_activity][months]`" x-model="row.punishments.rights_ban_professional_activity.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                                            <div><label class="inline-flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" x-model="arightsDrive" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_driving][enabled]`" value="1" form="{{ $formId }}">Жолоодох эрх</label><div x-show="arightsDrive" class="mt-1 grid grid-cols-2 gap-1"><input type="number" min="0" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_driving][years]`" x-model="row.punishments.rights_ban_driving.years" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" :name="`notes_defendant_sentences[{{ $defendantIndex }}][allocations][${allocIndex}][punishments][rights_ban_driving][months]`" x-model="row.punishments.rights_ban_driving.months" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                    <p class="mt-2 text-[11px] text-indigo-700">Олон зүйл ангид ял оноосон бол зүйл анги бүрт тусдаа мөрөөр хуваарилж бөглөнө.</p>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                            <div class="sticky bottom-0 z-10 flex items-center justify-between gap-2 border-t border-slate-200 bg-white px-4 py-3">
                                                @if(!$isClerkUser)
                                                    <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                                        <span class="relative inline-flex h-5 w-9 items-center">
                                                            <input type="checkbox" name="notes_handover_issued" value="1" form="{{ $formId }}" class="peer sr-only" @checked(old('notes_handover_issued', $h->notes_handover_issued))>
                                                            <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-emerald-500"></span>
                                                            <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                        </span>
                                                        <span>Тэмдэглэл гаргасан</span>
                                                    </label>
                                                @else
                                                    <span></span>
                                                @endif
                                                <div class="flex items-center gap-2">
                                                @if(\Illuminate\Support\Facades\Route::has($notesPrefix . '.notes.reschedule'))
                                                    <form method="POST" action="{{ route($notesPrefix . '.notes.reschedule', $h) }}" x-show="['Хойшилсон', 'Завсарласан', '60 хүртэлх хоногоор хойшлуулсан'].includes(decisionStatus)" x-cloak>
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100">
                                                            Хурлыг дахин зарлах
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" @click="cancel()" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Цуцлах</button>
                                                <button type="submit" form="{{ $formId }}" @click="openModal = false" class="inline-flex items-center rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Хадгалах</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <form id="{{ $formId }}" method="POST" action="{{ route($notesPrefix . '.notes.update', $h) }}" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                </form>
                            </td>
                            </tr>
                    @empty
                        <tr>
                            <td colspan="21" class="px-3 py-4 text-center text-slate-500 text-sm">
                                Мэдээлэл олдсонгүй.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/30">
            {{ $hearings->links() }}
        </div>
    </div>
</div>
@endsection

