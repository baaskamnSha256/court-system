
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
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="font-semibold">Хадгалах үед алдаа гарлаа:</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                            $defendantRegistriesList = is_array($h->defendant_registries ?? null)
                                ? array_values($h->defendant_registries)
                                : [];
                            $registryByDefendantName = collect($defendantNamesList)
                                ->mapWithKeys(function ($name, $index) use ($defendantRegistriesList) {
                                    return [trim((string) $name) => mb_strtoupper(trim((string) ($defendantRegistriesList[$index] ?? '')), 'UTF-8')];
                                })
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

                            $decisionStatus = (string) ($h->notes_decision_status ?? '');
                            $decisionCellClass = match ($decisionStatus) {
                                'Шийдвэрлэсэн' => 'bg-emerald-50/70',
                                'Хойшилсон' => 'bg-amber-50/70',
                                'Завсарласан' => 'bg-sky-50/70',
                                'Түдгэлзүүлсэн' => 'bg-orange-50/70',
                                'Прокурорт буцаасан' => 'bg-rose-50/70',
                                'Яллагдагчийг шүүхэд шилжүүлсэн' => 'bg-indigo-50/70',
                                '60 хүртэлх хоногоор хойшлуулсан' => 'bg-violet-50/70',
                                default => 'bg-slate-50/70',
                            };

                            $decisionBadgeClass = match ($decisionStatus) {
                                'Шийдвэрлэсэн' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Хойшилсон' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'Завсарласан' => 'bg-sky-100 text-sky-800 border-sky-200',
                                'Түдгэлзүүлсэн' => 'bg-orange-100 text-orange-800 border-orange-200',
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
                            $isOldInputForCurrentHearing = (string) old('hearing_id', '') === (string) $h->id;
                            $oldForCurrentHearing = function (string $key, mixed $default = null) use ($isOldInputForCurrentHearing) {
                                return $isOldInputForCurrentHearing ? old($key, $default) : $default;
                            };
                        @endphp
                        @php
                            $formId = 'notes-form-' . $h->id;
                            $savedDefendantsForModal = [];
                            foreach ($defendantNamesList as $defendantIndex => $defendantName) {
                                $sentencePrefillModal = $storedDefendantSentences[$defendantIndex] ?? ($storedSentencesByName[trim((string) $defendantName)] ?? []);
                                $allocationsPrefillModal = is_array($sentencePrefillModal['allocations'] ?? null) ? $sentencePrefillModal['allocations'] : [];
                                $decidedMatterIdsModal = array_values(array_map('intval', (array) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.decided_matter_ids", $sentencePrefillModal['decided_matter_ids'] ?? [])));
                                if (empty($decidedMatterIdsModal) && ! empty($allocationsPrefillModal)) {
                                    $decidedMatterIdsModal = collect($allocationsPrefillModal)
                                        ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                                        ->filter(fn ($id) => $id > 0)
                                        ->unique()
                                        ->values()
                                        ->all();
                                }
                                $otModal = $sentencePrefillModal['outcome_track'] ?? null;
                                if (! in_array($otModal, ['sentence', 'no_sentence', 'termination'], true)) {
                                    if (! empty($sentencePrefillModal['special_outcome'] ?? '')) {
                                        $otModal = 'no_sentence';
                                    } elseif (! empty($sentencePrefillModal['termination_kind'] ?? '') || trim((string) ($sentencePrefillModal['termination_note'] ?? '')) !== '') {
                                        $otModal = 'termination';
                                    } else {
                                        $otModal = 'sentence';
                                    }
                                }
                                $tkModal = ($sentencePrefillModal['termination_kind'] ?? '');
                                $tkModal = in_array($tkModal, ['dismiss', 'acquit'], true) ? $tkModal : '';
                                $tnModal = (string) ($sentencePrefillModal['termination_note'] ?? '');
                                $savedDefendantsForModal[] = [
                                    'defendant_registry' => $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.defendant_registry", $sentencePrefillModal['defendant_registry'] ?? ($registryByDefendantName[trim((string) $defendantName)] ?? '')),
                                    'decided_matter_ids' => $decidedMatterIdsModal,
                                    'outcome_track' => $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.outcome_track", $otModal),
                                    'termination_kind' => $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.termination_kind", $tkModal),
                                    'termination_note' => $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.termination_note", $tnModal),
                                    'special_outcome' => $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.special_outcome", $sentencePrefillModal['special_outcome'] ?? ''),
                                    'punishments' => is_array($sentencePrefillModal['punishments'] ?? null) ? $sentencePrefillModal['punishments'] : [],
                                    'allocations' => array_values($allocationsPrefillModal),
                                ];
                            }
                            $notesHandoverRowConfig = [
                                'hearingId' => $h->id,
                                'formId' => $formId,
                                'savedNotesHandoverText' => $oldForCurrentHearing('notes_handover_text', $h->notes_handover_text ?? ''),
                                'savedDecisionStatus' => $oldForCurrentHearing('notes_decision_status', $h->notes_decision_status ?? ''),
                                'savedClerkId' => $oldForCurrentHearing('clerk_id', $h->clerk_id),
                                'savedNotesHandoverIssued' => (bool) $oldForCurrentHearing('notes_handover_issued', $h->notes_handover_issued),
                                'savedDefendants' => $savedDefendantsForModal,
                            ];
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                            x-data="notesHandoverRow(@js($notesHandoverRowConfig))">
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
                                <div class="min-h-[72px] w-full rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-xs whitespace-pre-wrap break-words text-slate-700">
                                    {{ $h->notes_handover_text ?: '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words min-w-[300px]">
                                @php
                                    $decidedMatterLines = collect($storedDefendantSentences)
                                        ->filter(fn ($sentence) => is_array($sentence))
                                        ->map(function ($sentence) use ($allMatterCategories) {
                                            $name = trim((string) ($sentence['defendant_name'] ?? ''));
                                            $ids = array_values(array_filter(array_map('intval', (array) ($sentence['decided_matter_ids'] ?? []))));
                                            $matterNames = ($allMatterCategories ?? collect())
                                                ->whereIn('id', $ids)
                                                ->pluck('name')
                                                ->filter()
                                                ->values()
                                                ->all();
                                            if ($name === '' || empty($matterNames)) {
                                                return null;
                                            }

                                            return [
                                                'defendant_name' => $name,
                                                'matter_text' => implode(', ', $matterNames),
                                            ];
                                        })
                                        ->filter()
                                        ->values();
                                @endphp
                                @if($decidedMatterLines->isNotEmpty())
                                    @foreach($decidedMatterLines as $line)
                                        <div class="text-xs text-slate-700 whitespace-normal break-words">
                                            <span class="font-semibold">{{ $line['defendant_name'] }}:</span>
                                            <span>{{ $line['matter_text'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-xs text-slate-700 whitespace-normal break-words">
                                        {{ $h->notes_decided_matter ?: '—' }}
                                    </div>
                                @endif
                                @php
                                    $chipOptions = ($allMatterCategories ?? collect())
                                        ->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name])
                                        ->values();
                                    $originChipSelected = ($allMatterCategories ?? collect())
                                        ->whereIn('name', $matterCategories)
                                        ->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name])
                                        ->values();
                                    $chipSelected = collect($oldForCurrentHearing('notes_decided_matter_ids', $decidedMatterSelectedIds))
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
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words {{ $decisionCellClass }}">
                                @php
                                    $decisionOptions = [
                                        '' => '— Сонгох —',
                                        'Шийдвэрлэсэн' => 'Шийдвэрлэсэн',
                                        'Хойшилсон' => 'Хойшилсон',
                                        'Завсарласан' => 'Завсарласан',
                                        'Түдгэлзүүлсэн' => 'Түдгэлзүүлсэн',
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
                                        <option value="{{ $val }}" @selected($oldForCurrentHearing('notes_decision_status', $h->notes_decision_status) === $val)>{{ $label }}</option>
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
                                            <option value="{{ $clerk->id }}" @selected($oldForCurrentHearing('clerk_id', $h->clerk_id) == $clerk->id)>{{ $clerk->name }}</option>
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
                                                  @checked($oldForCurrentHearing('notes_handover_issued', $h->notes_handover_issued))>
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
                                    <button type="button" @click="openEditModal()"
                                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Засварлах
                                    </button>
                                </div>
                                <template x-teleport="body">
                                    <div x-show="openModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/65 backdrop-blur-[1px] p-4">
                                        <div @click.outside="cancel()" class="flex w-full max-w-4xl flex-col rounded-xl bg-white shadow-2xl max-h-[85vh] overflow-hidden">
                                            <div class="grid grid-cols-[1fr_auto_1fr] items-center border-b border-slate-200 px-4 py-3">
                                                <h3 class="col-start-2 text-center text-sm font-semibold text-slate-800">Шүүх хуралдааны тойм засварлах</h3>
                                                <button type="button" @click="cancel()" class="col-start-3 justify-self-end rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Хаах</button>
                                            </div>
                                            <div class="flex-1 overflow-y-auto px-4 py-3">
                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-center text-xs font-medium text-slate-600">Шүүх хуралдааны тойм</label>
                                                <textarea id="notes-handover-textarea-{{ $h->id }}" x-model="notesHandoverText" rows="3" form="{{ $formId }}" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500"></textarea>
                                                <input type="hidden" name="notes_handover_text" x-model="notesHandoverText" form="{{ $formId }}">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-center text-xs font-medium text-slate-600">Шүүх хуралдааны шийдвэр</label>
                                                <select name="notes_decision_status" x-model="decisionStatus" form="{{ $formId }}" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                                                    @foreach($decisionOptions as $val => $label)
                                                        <option value="{{ $val }}" @selected($oldForCurrentHearing('notes_decision_status', $h->notes_decision_status) === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if(!$isClerkUser)
                                                <div>
                                                    <label class="mb-1 block text-center text-xs font-medium text-slate-600">ШХНБДарга</label>
                                                    <select name="clerk_id" form="{{ $formId }}" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-center text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                                                        <option value="">— Сонгоогүй —</option>
                                                        @foreach($clerks as $clerk)
                                                            <option value="{{ $clerk->id }}" @selected($oldForCurrentHearing('clerk_id', $h->clerk_id) == $clerk->id)>{{ $clerk->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                            <div class="md:col-span-2">
                                                <div x-show="decisionStatus === 'Шийдвэрлэсэн'" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                                    <h4 class="mb-2 text-xs font-semibold text-slate-700">Шүүгдэгч тус бүрийн шийтгэлийн мэдээлэл</h4>
                                                    <div class="space-y-3">
                                                        @foreach($defendantNamesList as $defendantIndex => $defendantName)
                                                            @php
                                                                $sentencePrefill = $storedDefendantSentences[$defendantIndex] ?? ($storedSentencesByName[trim((string) $defendantName)] ?? []);
                                                                $allocationsPrefill = is_array($sentencePrefill['allocations'] ?? null) ? $sentencePrefill['allocations'] : [];
                                                                $defendantRegistryValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.defendant_registry", $sentencePrefill['defendant_registry'] ?? ($registryByDefendantName[trim((string) $defendantName)] ?? ''));
                                                                $specialOutcomeValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.special_outcome", $sentencePrefill['special_outcome'] ?? '');
                                                                $outcomeTrackInitial = $sentencePrefill['outcome_track'] ?? null;
                                                                if (! in_array($outcomeTrackInitial, ['sentence', 'no_sentence', 'termination'], true)) {
                                                                    if (! empty($sentencePrefill['special_outcome'] ?? '')) {
                                                                        $outcomeTrackInitial = 'no_sentence';
                                                                    } elseif (! empty($sentencePrefill['termination_kind'] ?? '') || trim((string) ($sentencePrefill['termination_note'] ?? '')) !== '') {
                                                                        $outcomeTrackInitial = 'termination';
                                                                    } else {
                                                                        $outcomeTrackInitial = 'sentence';
                                                                    }
                                                                }
                                                                $terminationKindPrefill = $sentencePrefill['termination_kind'] ?? '';
                                                                $terminationKindPrefill = in_array($terminationKindPrefill, ['dismiss', 'acquit'], true) ? $terminationKindPrefill : '';
                                                                $terminationNotePrefill = (string) ($sentencePrefill['termination_note'] ?? '');
                                                                $outcomeTrackValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.outcome_track", $outcomeTrackInitial);
                                                                if (! in_array($outcomeTrackValue, ['sentence', 'no_sentence', 'termination'], true)) {
                                                                    $outcomeTrackValue = $outcomeTrackInitial;
                                                                }
                                                                $terminationKindValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.termination_kind", $terminationKindPrefill);
                                                                if (! in_array($terminationKindValue, ['dismiss', 'acquit'], true)) {
                                                                    $terminationKindValue = $terminationKindPrefill;
                                                                }
                                                                $terminationNoteValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.termination_note", $terminationNotePrefill);
                                                                $decidedMatterIdsRaw = collect($oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.decided_matter_ids", $sentencePrefill['decided_matter_ids'] ?? []))
                                                                    ->map(fn ($id) => (int) $id)
                                                                    ->filter(fn ($id) => $id > 0)
                                                                    ->values();
                                                                if ($decidedMatterIdsRaw->isEmpty()) {
                                                                    $decidedMatterIdsRaw = collect($allocationsPrefill)
                                                                        ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                                                                        ->filter(fn ($id) => $id > 0)
                                                                        ->unique()
                                                                        ->values();
                                                                }
                                                                $decidedMatterIdValue = $decidedMatterIdsRaw->first();
                                                                $defendantMatterOptions = ($allMatterCategories ?? collect())
                                                                    ->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
                                                                    ->values()
                                                                    ->all();
                                                                $specialOutcomeOptions = [
                                                                    'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
                                                                    'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
                                                                    'Хорих ял оногдуулахгүйгээр тэнссэн',
                                                                    'Эрүүгийн хариуцлагаас чөлөөлсөн',
                                                                ];
                                                            @endphp
                                                            <div class="rounded-lg border border-slate-200 bg-white p-3"
                                                                 x-data="{
                                                                    rowsOpen: true,
                                                                    allocKey: 0,
                                                                    decisionTab: @js($outcomeTrackValue === 'sentence' ? 'sentence' : ($outcomeTrackValue === 'no_sentence' ? 'probation' : ($terminationKindValue === 'acquit' ? 'acquit' : 'dismiss'))),
                                                                    outcomeTrack: @js($outcomeTrackValue),
                                                                    terminationKind: @js($terminationKindValue),
                                                                    terminationNote: @js($terminationNoteValue),
                                                                    specialOutcome: @js($specialOutcomeValue),
                                                                    decidedMatterId: @js($decidedMatterIdValue),
                                                                    matterOptions: @js($defendantMatterOptions),
                                                                    matterQuery: '',
                                                                    matterOpen: false,
                                                                    matterActiveIndex: -1,
                                                                    allocRows: @js(array_values($allocationsPrefill)),
                                                                    selectDecidedMatter(id) {
                                                                        const intId = Number(id);
                                                                        if (!intId) return;
                                                                        this.decidedMatterId = intId;
                                                                        this.matterOpen = false;
                                                                    },
                                                                    clearDecidedMatter() {
                                                                        this.decidedMatterId = null;
                                                                        this.decisionTab = 'sentence';
                                                                        this.outcomeTrack = 'sentence';
                                                                        this.specialOutcome = '';
                                                                        this.terminationKind = '';
                                                                        this.terminationNote = '';
                                                                        this.clearAllocationPunishments();
                                                                        this.allocRows = [];
                                                                        this.allocKey = (this.allocKey || 0) + 1;
                                                                        this.matterQuery = '';
                                                                        this.matterOpen = false;
                                                                    },
                                                                    decidedMatterName() {
                                                                        const found = this.matterOptions.find((opt) => Number(opt.id) === Number(this.decidedMatterId));
                                                                        return found ? found.name : '';
                                                                    },
                                                                    filteredMatterOptions() {
                                                                        const q = (this.matterQuery || '').trim().toLowerCase();
                                                                        if (q === '') {
                                                                            return this.matterOptions;
                                                                        }
                                                                        return this.matterOptions.filter((opt) => String(opt.name || '').toLowerCase().includes(q));
                                                                    },
                                                                    openMatterDropdown() {
                                                                        this.matterOpen = true;
                                                                        const items = this.filteredMatterOptions();
                                                                        this.matterActiveIndex = items.length > 0 ? 0 : -1;
                                                                    },
                                                                    moveMatterHighlight(step) {
                                                                        const items = this.filteredMatterOptions();
                                                                        if (items.length < 1) {
                                                                            this.matterActiveIndex = -1;
                                                                            return;
                                                                        }
                                                                        if (this.matterActiveIndex < 0) {
                                                                            this.matterActiveIndex = 0;
                                                                            return;
                                                                        }
                                                                        this.matterActiveIndex = (this.matterActiveIndex + step + items.length) % items.length;
                                                                    },
                                                                    chooseMatterByKeyboard() {
                                                                        const items = this.filteredMatterOptions();
                                                                        if (!this.matterOpen || items.length < 1) {
                                                                            return;
                                                                        }
                                                                        const idx = this.matterActiveIndex >= 0 ? this.matterActiveIndex : 0;
                                                                        const opt = items[idx] || items[0];
                                                                        if (opt) {
                                                                            this.selectDecidedMatter(opt.id);
                                                                        }
                                                                    },
                                                                    onDecisionTabChange() {
                                                                        if (!this.decidedMatterId) {
                                                                            return;
                                                                        }
                                                                        if (this.decisionTab === 'sentence') {
                                                                            this.outcomeTrack = 'sentence';
                                                                            this.specialOutcome = '';
                                                                            this.terminationKind = '';
                                                                            this.terminationNote = '';
                                                                        } else if (this.decisionTab === 'probation') {
                                                                            this.outcomeTrack = 'no_sentence';
                                                                            if (![
                                                                                'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
                                                                                'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
                                                                                'Хорих ял оногдуулахгүйгээр тэнссэн',
                                                                                'Эрүүгийн хариуцлагаас чөлөөлсөн',
                                                                            ].includes(this.specialOutcome)) {
                                                                                this.specialOutcome = '';
                                                                            }
                                                                            this.terminationKind = '';
                                                                            this.terminationNote = '';
                                                                            this.clearAllocationPunishments();
                                                                            this.allocRows = [];
                                                                            this.allocKey = (this.allocKey || 0) + 1;
                                                                        } else if (this.decisionTab === 'dismiss') {
                                                                            this.outcomeTrack = 'termination';
                                                                            this.terminationKind = 'dismiss';
                                                                            this.specialOutcome = '';
                                                                            this.clearAllocationPunishments();
                                                                            this.allocRows = [];
                                                                            this.allocKey = (this.allocKey || 0) + 1;
                                                                        } else if (this.decisionTab === 'acquit') {
                                                                            this.outcomeTrack = 'termination';
                                                                            this.terminationKind = 'acquit';
                                                                            this.specialOutcome = '';
                                                                            this.clearAllocationPunishments();
                                                                            this.allocRows = [];
                                                                            this.allocKey = (this.allocKey || 0) + 1;
                                                                        }
                                                                    },
                                                                    addAllocRow() {
                                                                        if (this.decisionTab !== 'sentence') return;
                                                                        this.allocRows.push({ matter_category_id: '', punishments: { fine: { fine_units: '', damage_amount: '' }, community_service: { hours: '' }, travel_restriction: { years: '', months: '' }, imprisonment_open: { years: '', months: '' }, imprisonment_closed: { years: '', months: '' }, rights_ban_public_service: { years: '', months: '' }, rights_ban_professional_activity: { years: '', months: '' }, rights_ban_driving: { years: '', months: '' } } });
                                                                    },
                                                                    removeAllocRow(index) { this.allocRows.splice(index, 1); },
                                                                    clearAllocationPunishments() {
                                                                        this.allocRows = this.allocRows.map((row) => ({ ...row, punishments: { fine: { fine_units: '', damage_amount: '' }, community_service: { hours: '' }, travel_restriction: { years: '', months: '' }, imprisonment_open: { years: '', months: '' }, imprisonment_closed: { years: '', months: '' }, rights_ban_public_service: { years: '', months: '' }, rights_ban_professional_activity: { years: '', months: '' }, rights_ban_driving: { years: '', months: '' } } }));
                                                                    }
                                                                 }"
                                                                 x-init="window.addEventListener('notes-handover-modal-open', (e) => {
                                                                    if (!e.detail || e.detail.hearingId !== {{ $h->id }}) return;
                                                                    const row = e.detail.defendants?.[{{ $defendantIndex }}];
                                                                    if (!row) return;
                                                                    let ot = row.outcome_track || 'sentence';
                                                                    if (!['sentence','no_sentence','termination'].includes(ot)) ot = 'sentence';
                                                                    this.outcomeTrack = ot;
                                                                    this.terminationKind = (row.termination_kind === 'dismiss' || row.termination_kind === 'acquit') ? row.termination_kind : '';
                                                                    this.terminationNote = row.termination_note || '';
                                                                    this.specialOutcome = row.special_outcome || '';
                                                                    this.decidedMatterId = Array.isArray(row.decided_matter_ids)
                                                                        ? (row.decided_matter_ids.map((id) => Number(id)).find((id) => id > 0) || null)
                                                                        : null;
                                                                    if (!this.decidedMatterId && Array.isArray(row.allocations)) {
                                                                        const allocMatter = row.allocations
                                                                            .map((a) => Number(a?.matter_category_id || 0))
                                                                            .find((id) => id > 0);
                                                                        this.decidedMatterId = allocMatter || null;
                                                                    }
                                                                    const punishments = (row.punishments && typeof row.punishments === 'object') ? row.punishments : {};
                                                                    const setCheckbox = (fieldName, checked) => {
                                                                        const el = $el.querySelector(`input[name='${fieldName}']`);
                                                                        if (!el) return;
                                                                        el.checked = !!checked;
                                                                        el.dispatchEvent(new Event('change', { bubbles: true }));
                                                                    };
                                                                    const setValue = (fieldName, value) => {
                                                                        const el = $el.querySelector(`input[name='${fieldName}']`);
                                                                        if (!el) return;
                                                                        el.value = value == null ? '' : String(value);
                                                                        el.dispatchEvent(new Event('input', { bubbles: true }));
                                                                    };
                                                                    const setTextareaValue = (fieldName, value) => {
                                                                        const el = $el.querySelector(`textarea[name='${fieldName}']`);
                                                                        if (!el) return;
                                                                        el.value = value == null ? '' : String(value);
                                                                        el.dispatchEvent(new Event('input', { bubbles: true }));
                                                                    };
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][fine][enabled]", !!(punishments.fine && Object.keys(punishments.fine).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][fine][fine_units]", punishments.fine?.fine_units ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][community_service][enabled]", !!(punishments.community_service && Object.keys(punishments.community_service).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][community_service][hours]", punishments.community_service?.hours ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][enabled]", !!(punishments.travel_restriction && Object.keys(punishments.travel_restriction).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][years]", punishments.travel_restriction?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][months]", punishments.travel_restriction?.months ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][enabled]", !!(punishments.imprisonment_open && Object.keys(punishments.imprisonment_open).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][years]", punishments.imprisonment_open?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][months]", punishments.imprisonment_open?.months ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][enabled]", !!(punishments.imprisonment_closed && Object.keys(punishments.imprisonment_closed).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][years]", punishments.imprisonment_closed?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][months]", punishments.imprisonment_closed?.months ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][enabled]", !!(punishments.rights_ban_public_service && Object.keys(punishments.rights_ban_public_service).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][years]", punishments.rights_ban_public_service?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][months]", punishments.rights_ban_public_service?.months ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][enabled]", !!(punishments.rights_ban_professional_activity && Object.keys(punishments.rights_ban_professional_activity).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][years]", punishments.rights_ban_professional_activity?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][months]", punishments.rights_ban_professional_activity?.months ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][enabled]", !!(punishments.rights_ban_driving && Object.keys(punishments.rights_ban_driving).length));
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][years]", punishments.rights_ban_driving?.years ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][months]", punishments.rights_ban_driving?.months ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][damage_amount]", punishments.damage_amount ?? punishments.fine?.damage_amount ?? '');
                                                                    setValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][compensated_damage_amount]", punishments.compensated_damage_amount ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][asset_confiscation]", !!punishments.asset_confiscation);
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][destroy_evidence]", !!punishments.destroy_evidence);
                                                                    setTextareaValue("notes_defendant_sentences[{{ $defendantIndex }}][punishments][other]", punishments.other ?? '');
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][damage_amount_enabled]", !!(punishments.damage_amount || punishments.fine?.damage_amount));
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][compensated_damage_amount_enabled]", !!punishments.compensated_damage_amount);
                                                                    setCheckbox("notes_defendant_sentences[{{ $defendantIndex }}][punishments][other_enabled]", !!(punishments.other && String(punishments.other).trim() !== ''));
                                                                    this.matterQuery = '';
                                                                    this.matterOpen = false;
                                                                    this.matterActiveIndex = -1;
                                                                    this.decisionTab = this.outcomeTrack === 'sentence'
                                                                        ? 'sentence'
                                                                        : (this.outcomeTrack === 'no_sentence'
                                                                            ? 'probation'
                                                                            : (this.terminationKind === 'acquit' ? 'acquit' : 'dismiss'));
                                                                    this.allocRows = Array.isArray(row.allocations) && row.allocations.length ? JSON.parse(JSON.stringify(row.allocations)) : [];
                                                                    this.allocKey = (this.allocKey || 0) + 1;
                                                                    const reg = $el.querySelector(&quot;input[name='notes_defendant_sentences[{{ $defendantIndex }}][defendant_registry]']&quot;);
                                                                    if (reg) reg.value = row.defendant_registry || '';
                                                                 })">
                                                                <input type="hidden" name="notes_defendant_sentences[{{ $defendantIndex }}][defendant_name]" value="{{ $defendantName }}" form="{{ $formId }}">
                                                                <div class="mb-2 text-xs font-semibold text-slate-700">{{ $defendantName }}</div>
                                                                <input type="hidden"
                                                                       name="notes_defendant_sentences[{{ $defendantIndex }}][defendant_registry]"
                                                                       value="{{ $defendantRegistryValue }}"
                                                                       form="{{ $formId }}">
                                                                <div class="mb-3 rounded border border-slate-200 bg-slate-50/60 p-2">
                                                                    <label class="mb-2 block text-xs font-semibold text-slate-700">Шийдвэрлэсэн зүйл анги</label>
                                                                    <div class="rounded-lg border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-300"
                                                                         @click.outside="matterOpen = false">
                                                                        <div class="flex flex-wrap items-center gap-1.5 px-2 py-1.5 min-h-[2.25rem]">
                                                                            <template x-if="decidedMatterId">
                                                                                <span class="inline-flex items-center gap-1 rounded border border-blue-300 bg-blue-100 px-2 py-0.5 text-[11px] text-blue-800">
                                                                                    <span x-text="decidedMatterName()"></span>
                                                                                    <button type="button" class="leading-none text-blue-700 hover:text-rose-600" @click.stop="clearDecidedMatter()">&times;</button>
                                                                                </span>
                                                                            </template>
                                                                            <input type="text"
                                                                                   x-model="matterQuery"
                                                                                   @focus="openMatterDropdown()"
                                                                                   @input="openMatterDropdown()"
                                                                                   @keydown.arrow-down.prevent="if (!matterOpen) { openMatterDropdown(); } else { moveMatterHighlight(1); }"
                                                                                   @keydown.arrow-up.prevent="if (!matterOpen) { openMatterDropdown(); } else { moveMatterHighlight(-1); }"
                                                                                   @keydown.enter.prevent="chooseMatterByKeyboard()"
                                                                                   @keydown.escape="matterOpen = false"
                                                                                   placeholder="Зүйл анги хайх..."
                                                                                   class="flex-1 min-w-[10rem] border-0 bg-transparent py-1 text-xs text-slate-700 focus:outline-none focus:ring-0">
                                                                        </div>
                                                                        <div x-show="matterOpen" x-cloak class="max-h-48 overflow-auto border-t border-slate-200">
                                                                            <template x-for="(opt, optIndex) in filteredMatterOptions()" :key="'matter-opt-'+opt.id">
                                                                                <button type="button"
                                                                                        @click="selectDecidedMatter(opt.id)"
                                                                                        :class="matterActiveIndex === optIndex ? 'bg-blue-100' : ''"
                                                                                        class="flex w-full items-center justify-between border-b border-slate-100 px-3 py-2 text-left text-xs hover:bg-slate-50">
                                                                                    <span x-text="opt.name"></span>
                                                                                    <span x-show="Number(decidedMatterId) === Number(opt.id)" class="font-semibold text-blue-700">✓</span>
                                                                                </button>
                                                                            </template>
                                                                            <div x-show="filteredMatterOptions().length === 0" class="px-3 py-2 text-xs text-slate-500">Олдсонгүй</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-1.5 text-[11px] text-slate-500">
                                                                        
                                                                        <span x-show="!decidedMatterId">Доорх шийдвэрийн төрлийг идэвхжүүлэхийн тулд зүйл анги сонгоно уу.</span>
                                                                    </div>
                                                                    <template x-if="decidedMatterId">
                                                                        <button type="button"
                                                                                @click="clearDecidedMatter()"
                                                                                class="mt-1 inline-flex items-center rounded border border-slate-300 bg-white px-2 py-0.5 text-[11px] text-slate-600 hover:bg-slate-50">
                                                                            Цэвэрлэх
                                                                        </button>
                                                                    </template>
                                                                    <input type="hidden" x-show="decidedMatterId" name="notes_defendant_sentences[{{ $defendantIndex }}][decided_matter_ids][]" :value="decidedMatterId" form="{{ $formId }}">
                                                                    <div class="mt-3 rounded border border-blue-200 bg-blue-50/30 p-2">
                                                                        <label class="mb-2 block text-xs font-semibold text-slate-700">Шийдвэрийн төрөл</label>
                                                                        <div x-show="decidedMatterId" class="border-b-2 border-blue-300/80 pb-0.5">
                                                                            <div class="grid grid-cols-2 gap-1 md:grid-cols-4">
                                                                                <button type="button" @click="decisionTab = 'sentence'; onDecisionTabChange()" :class="decisionTab === 'sentence' ? 'border-blue-500 bg-blue-500 text-white shadow-sm' : 'border-slate-300 bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-t-xl border border-b-0 px-2 py-1.5 text-xs font-semibold transition-colors">Ял оноох</button>
                                                                                <button type="button" @click="decisionTab = 'probation'; onDecisionTabChange()" :class="decisionTab === 'probation' ? 'border-blue-500 bg-blue-500 text-white shadow-sm' : 'border-slate-300 bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-t-xl border border-b-0 px-2 py-1.5 text-xs font-semibold transition-colors">Ял оногдуулахгүйгээр тэнсэх</button>
                                                                                <button type="button" @click="decisionTab = 'dismiss'; onDecisionTabChange()" :class="decisionTab === 'dismiss' ? 'border-blue-500 bg-blue-500 text-white shadow-sm' : 'border-slate-300 bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-t-xl border border-b-0 px-2 py-1.5 text-xs font-semibold transition-colors">Хэрэгсэхгүй болгох</button>
                                                                                <button type="button" @click="decisionTab = 'acquit'; onDecisionTabChange()" :class="decisionTab === 'acquit' ? 'border-blue-500 bg-blue-500 text-white shadow-sm' : 'border-slate-300 bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-t-xl border border-b-0 px-2 py-1.5 text-xs font-semibold transition-colors">Цагаатгах</button>
                                                                            </div>
                                                                        </div>
                                                                        <p x-show="!decidedMatterId" class="text-[11px] text-slate-500">Эхлээд шийдвэрлэсэн зүйл анги сонгоно уу. Дараа нь шийдвэрийн tab идэвхжинэ.</p>
                                                                        <input type="hidden" name="notes_defendant_sentences[{{ $defendantIndex }}][outcome_track]" :value="decisionTab === 'sentence' ? 'sentence' : (decisionTab === 'probation' ? 'no_sentence' : 'termination')" form="{{ $formId }}">
                                                                        <input type="hidden" name="notes_defendant_sentences[{{ $defendantIndex }}][special_outcome]" :value="decisionTab === 'probation' ? specialOutcome : ''" form="{{ $formId }}">
                                                                        <input type="hidden" name="notes_defendant_sentences[{{ $defendantIndex }}][termination_kind]" :value="decisionTab === 'dismiss' ? 'dismiss' : (decisionTab === 'acquit' ? 'acquit' : '')" form="{{ $formId }}">
                                                                        <div x-show="decisionTab === 'probation'" x-cloak class="mt-2 rounded border border-blue-200 bg-blue-50/30 p-2">
                                                                            <div class="grid grid-cols-1 gap-1 md:grid-cols-2">
                                                                                @foreach($specialOutcomeOptions as $option)
                                                                                    <label class="inline-flex items-start gap-2 rounded border border-blue-100 bg-white px-2 py-1 text-[11px] text-slate-700">
                                                                                        <span class="relative mt-0.5 inline-flex h-5 w-9 items-center">
                                                                                            <input type="radio" x-model="specialOutcome" value="{{ $option }}" class="peer sr-only">
                                                                                            <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                            <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                        </span>
                                                                                        <span>{{ $option }}</span>
                                                                                    </label>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                <div class="mb-3 rounded border border-violet-200 bg-violet-50/40 p-2" x-show="decisionTab === 'dismiss' || decisionTab === 'acquit'">
                                                                    <label class="mb-1 block text-[11px] font-medium text-violet-900"></label>
                                                                    <textarea name="notes_defendant_sentences[{{ $defendantIndex }}][termination_note]" x-model="terminationNote" rows="2" form="{{ $formId }}" :disabled="!(decisionTab === 'dismiss' || decisionTab === 'acquit')" placeholder="Шийдвэрийн утгыг бичнэ үү" class="w-full rounded border border-violet-200 px-2 py-1 text-xs">{{ $terminationNoteValue }}</textarea>
                                                                </div>
                                                                @php
                                                                    $punishmentsPrefill = is_array($sentencePrefill['punishments'] ?? null) ? $sentencePrefill['punishments'] : [];
                                                                    $fineEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.fine.enabled", ! empty($punishmentsPrefill['fine'] ?? []));
                                                                    $fineUnitsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.fine.fine_units", $punishmentsPrefill['fine']['fine_units'] ?? '');
                                                                    $communityEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.community_service.enabled", ! empty($punishmentsPrefill['community_service'] ?? []));
                                                                    $communityHoursValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.community_service.hours", $punishmentsPrefill['community_service']['hours'] ?? '');
                                                                    $travelEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.travel_restriction.enabled", ! empty($punishmentsPrefill['travel_restriction'] ?? []));
                                                                    $travelYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.travel_restriction.years", $punishmentsPrefill['travel_restriction']['years'] ?? '');
                                                                    $travelMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.travel_restriction.months", $punishmentsPrefill['travel_restriction']['months'] ?? '');
                                                                    $imprOpenEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_open.enabled", ! empty($punishmentsPrefill['imprisonment_open'] ?? []));
                                                                    $imprOpenYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_open.years", $punishmentsPrefill['imprisonment_open']['years'] ?? '');
                                                                    $imprOpenMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_open.months", $punishmentsPrefill['imprisonment_open']['months'] ?? '');
                                                                    $imprClosedEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_closed.enabled", ! empty($punishmentsPrefill['imprisonment_closed'] ?? []));
                                                                    $imprClosedYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_closed.years", $punishmentsPrefill['imprisonment_closed']['years'] ?? '');
                                                                    $imprClosedMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.imprisonment_closed.months", $punishmentsPrefill['imprisonment_closed']['months'] ?? '');
                                                                    $rightsPublicEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_public_service.enabled", ! empty($punishmentsPrefill['rights_ban_public_service'] ?? []));
                                                                    $rightsPublicYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_public_service.years", $punishmentsPrefill['rights_ban_public_service']['years'] ?? '');
                                                                    $rightsPublicMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_public_service.months", $punishmentsPrefill['rights_ban_public_service']['months'] ?? '');
                                                                    $rightsProEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_professional_activity.enabled", ! empty($punishmentsPrefill['rights_ban_professional_activity'] ?? []));
                                                                    $rightsProYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_professional_activity.years", $punishmentsPrefill['rights_ban_professional_activity']['years'] ?? '');
                                                                    $rightsProMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_professional_activity.months", $punishmentsPrefill['rights_ban_professional_activity']['months'] ?? '');
                                                                    $rightsDriveEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_driving.enabled", ! empty($punishmentsPrefill['rights_ban_driving'] ?? []));
                                                                    $rightsDriveYearsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_driving.years", $punishmentsPrefill['rights_ban_driving']['years'] ?? '');
                                                                    $rightsDriveMonthsValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.rights_ban_driving.months", $punishmentsPrefill['rights_ban_driving']['months'] ?? '');
                                                                    $damageAmountValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.damage_amount", $punishmentsPrefill['damage_amount'] ?? ($punishmentsPrefill['fine']['damage_amount'] ?? ''));
                                                                    $compensatedDamageAmountValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.compensated_damage_amount", $punishmentsPrefill['compensated_damage_amount'] ?? '');
                                                                    $assetConfiscationValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.asset_confiscation", (bool) ($punishmentsPrefill['asset_confiscation'] ?? false));
                                                                    $destroyEvidenceValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.destroy_evidence", (bool) ($punishmentsPrefill['destroy_evidence'] ?? false));
                                                                    $otherPunishmentValue = $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.other", $punishmentsPrefill['other'] ?? '');
                                                                    $damageAmountEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.damage_amount_enabled", trim((string) $damageAmountValue) !== '');
                                                                    $compensatedDamageAmountEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.compensated_damage_amount_enabled", trim((string) $compensatedDamageAmountValue) !== '');
                                                                    $otherPunishmentEnabledValue = (bool) $oldForCurrentHearing("notes_defendant_sentences.{$defendantIndex}.punishments.other_enabled", trim((string) $otherPunishmentValue) !== '');
                                                                @endphp
                                                                <fieldset x-show="decidedMatterId && decisionTab === 'sentence'" x-cloak class="mt-2 rounded border border-blue-200 bg-white p-2" :disabled="!decidedMatterId" :class="!decidedMatterId ? 'opacity-60' : ''"
                                                                          x-data="{ fineEnabled: @js($fineEnabledValue), communityEnabled: @js($communityEnabledValue), travelEnabled: @js($travelEnabledValue), imprOpenEnabled: @js($imprOpenEnabledValue), imprClosedEnabled: @js($imprClosedEnabledValue), rightsPublicEnabled: @js($rightsPublicEnabledValue), rightsProEnabled: @js($rightsProEnabledValue), rightsDriveEnabled: @js($rightsDriveEnabledValue), damageAmountEnabled: @js($damageAmountEnabledValue), compensatedDamageAmountEnabled: @js($compensatedDamageAmountEnabledValue), otherPunishmentEnabled: @js($otherPunishmentEnabledValue) }">
                                                                    <div class="mb-2 text-xs font-semibold text-blue-800"></div>
                                                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                        <div class="rounded border border-slate-200 p-2">
                                                                            <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                <span class="truncate">Торгох</span>
                                                                                <span class="relative inline-flex h-5 w-9 items-center">
                                                                                    <input type="checkbox" x-model="fineEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][fine][enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                    <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                    <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                </span>
                                                                            </label>
                                                                            <div x-show="fineEnabled" x-cloak class="mt-2">
                                                                                <input type="text" value="{{ $fineUnitsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][fine][fine_units]" @input="formatGroupedInput($event)" placeholder="Торгох нэгж" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                            </div>
                                                                        </div>
                                                                        <div class="rounded border border-slate-200 p-2">
                                                                            <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                <span class="truncate">Нийтэд тустай ажил</span>
                                                                                <span class="relative inline-flex h-5 w-9 items-center">
                                                                                    <input type="checkbox" x-model="communityEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][community_service][enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                    <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                    <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                </span>
                                                                            </label>
                                                                            <div x-show="communityEnabled" x-cloak class="mt-2">
                                                                                <input type="number" min="0" max="720" value="{{ $communityHoursValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][community_service][hours]" placeholder="Цаг (max 720)" form="{{ $formId }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                                            </div>
                                                                        </div>
                                                                        <div class="rounded border border-slate-200 p-2">
                                                                            <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                <span class="truncate">Зорчих эрх</span>
                                                                                <span class="relative inline-flex h-5 w-9 items-center">
                                                                                    <input type="checkbox" x-model="travelEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                    <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                    <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                </span>
                                                                            </label>
                                                                            <div x-show="travelEnabled" x-cloak class="mt-2 grid grid-cols-2 gap-2">
                                                                                <input type="number" min="0" value="{{ $travelYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                <input type="number" min="0" max="12" value="{{ $travelMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][travel_restriction][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                                            </div>
                                                                        </div>
                                                                        <div class="rounded border border-slate-200 p-2">
                                                                            <div class="text-xs font-medium text-slate-700">Хорих ял</div>
                                                                            <div class="mt-2 space-y-2">
                                                                                <label class="inline-flex items-center gap-2 text-xs text-slate-700"><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" x-model="imprOpenEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][enabled]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span>Нээлттэй</label>
                                                                                <div x-show="imprOpenEnabled" x-cloak class="grid grid-cols-2 gap-2"><input type="number" min="0" value="{{ $imprOpenYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" value="{{ $imprOpenMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_open][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div>
                                                                                <label class="inline-flex items-center gap-2 text-xs text-slate-700"><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" x-model="imprClosedEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][enabled]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span>Хаалттай</label>
                                                                                <div x-show="imprClosedEnabled" x-cloak class="grid grid-cols-2 gap-2"><input type="number" min="0" value="{{ $imprClosedYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" value="{{ $imprClosedMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][imprisonment_closed][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="rounded border border-slate-200 p-2 md:col-span-2">
                                                                            <div class="text-xs font-medium text-slate-700">Эрх хасах</div>
                                                                            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-3">
                                                                                <div class="rounded border border-slate-200 p-2"><label class="inline-flex w-full items-center justify-between gap-2 text-xs text-slate-700"><span class="truncate">Нийтийн алба</span><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" x-model="rightsPublicEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][enabled]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span></label><div x-show="rightsPublicEnabled" x-cloak class="mt-2 grid grid-cols-2 gap-1"><input type="number" min="0" value="{{ $rightsPublicYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" value="{{ $rightsPublicMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_public_service][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                                <div class="rounded border border-slate-200 p-2"><label class="inline-flex w-full items-center justify-between gap-2 text-xs text-slate-700"><span class="truncate">Мэргэжлийн эрх</span><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" x-model="rightsProEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][enabled]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span></label><div x-show="rightsProEnabled" x-cloak class="mt-2 grid grid-cols-2 gap-1"><input type="number" min="0" value="{{ $rightsProYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" value="{{ $rightsProMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_professional_activity][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                                <div class="rounded border border-slate-200 p-2"><label class="inline-flex w-full items-center justify-between gap-2 text-xs text-slate-700"><span class="truncate">Жолоодох эрх</span><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" x-model="rightsDriveEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][enabled]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span></label><div x-show="rightsDriveEnabled" x-cloak class="mt-2 grid grid-cols-2 gap-1"><input type="number" min="0" value="{{ $rightsDriveYearsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][years]" placeholder="Жил" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"><input type="number" min="0" max="12" value="{{ $rightsDriveMonthsValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][rights_ban_driving][months]" placeholder="Сар" form="{{ $formId }}" class="rounded border border-slate-300 px-2 py-1 text-xs"></div></div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="rounded border border-slate-200 p-2 md:col-span-2">
                                                                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                                <div class="flex h-full flex-col rounded border border-slate-200 p-2">
                                                                                    <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                        <span class="truncate">Хохирлын дүн</span>
                                                                                        <span class="relative inline-flex h-5 w-9 items-center">
                                                                                            <input type="checkbox" x-model="damageAmountEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][damage_amount_enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                            <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                            <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                        </span>
                                                                                    </label>
                                                                                    <input x-show="damageAmountEnabled" x-cloak :disabled="!damageAmountEnabled" type="text" value="{{ $damageAmountValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][damage_amount]" @input="formatGroupedInput($event)" placeholder="Хохирлын дүн" form="{{ $formId }}" class="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                </div>
                                                                                <div class="flex h-full flex-col rounded border border-slate-200 p-2">
                                                                                    <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                        <span class="truncate">Шүүхийн шатанд нөхөн төлүүлсэн хохирлын хэмжээ</span>
                                                                                        <span class="relative inline-flex h-5 w-9 items-center">
                                                                                            <input type="checkbox" x-model="compensatedDamageAmountEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][compensated_damage_amount_enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                            <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                            <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                        </span>
                                                                                    </label>
                                                                                    <input x-show="compensatedDamageAmountEnabled" x-cloak :disabled="!compensatedDamageAmountEnabled" type="text" value="{{ $compensatedDamageAmountValue }}" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][compensated_damage_amount]" @input="formatGroupedInput($event)" placeholder="Шүүхийн шатанд нөхөн төлүүлсэн хохирлын хэмжээ" form="{{ $formId }}" class="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                                                </div>
                                                                            </div>
                                                                            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                                <div class="rounded border border-slate-200 p-2"><label class="inline-flex w-full items-center justify-between gap-2 text-xs text-slate-700"><span class="truncate">Хөрөнгө орлого хураах</span><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" @checked($assetConfiscationValue) name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][asset_confiscation]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span></label></div>
                                                                                <div class="rounded border border-slate-200 p-2"><label class="inline-flex w-full items-center justify-between gap-2 text-xs text-slate-700"><span class="truncate">Эд мөрийн баримт устгуулах</span><span class="relative inline-flex h-5 w-9 items-center"><input type="checkbox" @checked($destroyEvidenceValue) name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][destroy_evidence]" value="1" form="{{ $formId }}" class="peer sr-only"><span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span><span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span></span></label></div>
                                                                            </div>
                                                                            <div class="mt-2 rounded border border-slate-200 p-2">
                                                                                <label class="inline-flex w-full items-center justify-between gap-2 text-xs font-medium text-slate-700">
                                                                                    <span>Бусад</span>
                                                                                    <span class="relative inline-flex h-5 w-9 items-center">
                                                                                        <input type="checkbox" x-model="otherPunishmentEnabled" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][other_enabled]" value="1" form="{{ $formId }}" class="peer sr-only">
                                                                                        <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-500"></span>
                                                                                        <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                                                    </span>
                                                                                </label>
                                                                                <textarea x-show="otherPunishmentEnabled" x-cloak :disabled="!otherPunishmentEnabled" rows="2" name="notes_defendant_sentences[{{ $defendantIndex }}][punishments][other]" placeholder="Бусад" form="{{ $formId }}" class="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs">{{ $otherPunishmentValue }}</textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                                
                                                        
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                            </div>
                                            <div class="mt-auto shrink-0 border-t border-slate-200 bg-slate-50/95 px-4 py-3">
                                                <div class="flex w-full items-center justify-end gap-2">
                                                @if(!$isClerkUser)
                                                    <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                                        <span class="relative inline-flex h-5 w-9 items-center">
                                                            <input type="checkbox" name="notes_handover_issued" value="1" form="{{ $formId }}" class="peer sr-only" @checked($oldForCurrentHearing('notes_handover_issued', $h->notes_handover_issued))>
                                                            <span class="h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-emerald-500"></span>
                                                            <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                                        </span>
                                                        <span>Тэмдэглэл гаргасан</span>
                                                    </label>
                                                @endif
                                                    @if(\Illuminate\Support\Facades\Route::has($notesPrefix . '.notes.reschedule'))
                                                        <form method="POST" action="{{ route($notesPrefix . '.notes.reschedule', $h) }}" x-show="((decisionStatus || '').trim() !== '') && ((decisionStatus || '').trim() !== 'Шийдвэрлэсэн')" x-cloak>
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100">
                                                                Хурлыг дахин зарлах
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <button type="button" @click="cancel()" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Цуцлах</button>
                                                    <button
                                                        type="submit"
                                                        form="{{ $formId }}"
                                                        class="inline-flex items-center rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700"
                                                    >
                                                        Хадгалах
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <form id="{{ $formId }}" method="POST" action="{{ route($notesPrefix . '.notes.update', $h) }}" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="hearing_id" value="{{ $h->id }}">
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

