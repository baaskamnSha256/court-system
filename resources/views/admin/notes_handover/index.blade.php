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
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шийдвэрлэсэн зүйл анги</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Торгох нэгж</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Хохирлын дүн</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Шүүх хуралдааны шийдвэр</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">ШХНБ дарга</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">ШХНБ дарга сонгосон цаг</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Тэмдэглэл гаргасан эсэх</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Бүртгэсэн цаг</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-normal break-words text-sm">Үйлдэл</th>
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
                        @endphp
                        @php
                            $formId = 'notes-form-' . $h->id;
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                            x-data="{ edit: false, cancel() { this.edit = false; document.getElementById('{{ $formId }}')?.reset(); } }">
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
                                <textarea name="notes_handover_text" rows="3" :disabled="!edit" form="{{ $formId }}"
                                          :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
                                          class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">{{ old('notes_handover_text', $h->notes_handover_text) }}</textarea>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <div x-show="!edit" class="text-xs text-slate-700 whitespace-normal break-words">
                                    {{ $h->notes_decided_matter ?: '—' }}
                                </div>
                                @php
                                    $chipOptions = ($allMatterCategories ?? collect())
                                        ->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name])
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

                                <div x-show="edit" x-cloak
                                     x-data="chipSelect({
                                        options: @js($chipOptions),
                                        selected: @js($chipSelected),
                                        single: false,
                                        placeholder: 'Зүйл анги хайх...',
                                        nameId: 'notes_decided_matter_ids[]'
                                     })"
                                     x-init="init()"
                                     class="mt-0.5">
                                    <div class="rounded-lg border border-slate-300 bg-white focus-within:ring-1 focus-within:ring-sky-300 focus-within:border-sky-500"
                                         :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
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
                                                   @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                                                   placeholder="Зүйл анги хайх..."
                                                   class="flex-1 min-w-[8rem] border-0 py-1 text-xs focus:ring-0 focus:outline-none">
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
                                       value="{{ old('notes_fine_units', $h->notes_fine_units) }}"
                                       :disabled="!edit" form="{{ $formId }}"
                                       :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
                                       class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
                                <input type="text" name="notes_damage_amount"
                                       value="{{ old('notes_damage_amount', $h->notes_damage_amount) }}"
                                       :disabled="!edit" form="{{ $formId }}"
                                       :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
                                       class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed">
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-normal break-words">
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
                                <select name="notes_decision_status"
                                        :disabled="!edit" form="{{ $formId }}" required
                                        :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
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
                                    <select name="clerk_id" :disabled="!edit" required
                                            form="{{ $formId }}"
                                            :class="edit ? 'border-sky-500 ring-1 ring-sky-300' : ''"
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
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1 text-xs">
                                    <label class="inline-flex items-center gap-1 text-slate-700">
                                        @if(!$isClerkUser)
                                            <input type="checkbox" name="notes_handover_issued" value="1"
                                                   :disabled="!edit"
                                                   form="{{ $formId }}"
                                                   class="rounded border-slate-300 disabled:cursor-not-allowed"
                                                   @checked(old('notes_handover_issued', $h->notes_handover_issued))>
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
                            <td class="px-3 py-2.5 text-slate-700 align-top whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" x-show="!edit" @click="edit = true"
                                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Засварлах
                                    </button>
                                    <button type="submit" x-show="edit" form="{{ $formId }}"
                                            class="inline-flex items-center rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                                        Хадгалах
                                    </button>
                                    <button type="button" x-show="edit" @click="cancel()"
                                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Цуцлах
                                    </button>
                                </div>
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

