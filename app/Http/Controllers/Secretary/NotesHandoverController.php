<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotesHandoverController extends Controller
{
    public function index(Request $request)
    {
        $query = Hearing::query()->with(['judges', 'prosecutor'])
            ->where('created_by', auth()->id());

        if ($request->filled('hearing_date')) {
            $query->whereDate('hearing_date', $request->date('hearing_date'));
        }
        if ($request->filled('hearing_date_from') && $request->filled('hearing_date_to')) {
            $from = $request->date('hearing_date_from')->startOfDay();
            $to = $request->date('hearing_date_to')->endOfDay();
            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($qq) use ($q) {
                $qq->where('case_no', 'like', "%{$q}%")
                    ->orWhere('courtroom', 'like', "%{$q}%")
                    ->orWhere('defendants', 'like', "%{$q}%");
            });
        }

        if ($request->filled('notes_decision_status')) {
            $status = (string) $request->input('notes_decision_status');
            if ($status === '__pending__') {
                $known = [
                    'Шийдвэрлэсэн',
                    'Хойшилсон',
                    'Завсарласан',
                    'Прокурорт буцаасан',
                    'Яллагдагчийг шүүхэд шилжүүлсэн',
                    '60 хүртэлх хоногоор хойшлуулсан',
                ];
                $query->where(function ($q) use ($known) {
                    $q->whereNull('notes_decision_status')
                        ->orWhere('notes_decision_status', '')
                        ->orWhereNotIn('notes_decision_status', $known);
                });
            } else {
                $query->where('notes_decision_status', $status);
            }
        }

        $hearings = $query
            ->orderBy('hearing_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->orderBy('courtroom')
            ->paginate(20)
            ->withQueryString();

        $clerks = User::role('court_clerk')->orderBy('name')->get();
        $matterCategories = MatterCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.notes_handover.index', [
            'headerTitle' => 'Тэмдэглэл хүлээлцэх',
            'hearings' => $hearings,
            'clerks' => $clerks,
            'allMatterCategories' => $matterCategories,
            'notesRoutePrefix' => 'secretary',
        ]);
    }

    public function update(Request $request, Hearing $hearing)
    {
        // Secretary зөвхөн өөрийн оруулсан хурлыг засна
        if ((int) $hearing->created_by !== (int) auth()->id()) {
            abort(403, 'Та зөвхөн өөрийн оруулсан хурлын тэмдэглэлийг шинэчилж чадна.');
        }
        $oldClerkId = $hearing->clerk_id;
        $oldIssued = (bool) $hearing->notes_handover_issued;

        $data = $request->validate([
            'notes_handover_text' => ['nullable', 'string'],
            'notes_decided_matter' => ['nullable', 'string', 'max:255'],
            'notes_decided_matter_ids' => ['nullable', 'array'],
            'notes_decided_matter_ids.*' => ['integer', 'exists:matter_categories,id'],
            'notes_fine_units' => ['nullable', 'string', 'max:100'],
            'notes_damage_amount' => ['nullable', 'string', 'max:100'],
            'notes_decision_status' => ['required', 'string', 'max:100'],
            'notes_defendant_sentences' => ['nullable', 'array'],
            'notes_defendant_sentences.*.defendant_name' => ['nullable', 'string', 'max:255'],
            'notes_defendant_sentences.*.decided_matter_ids' => ['nullable', 'array'],
            'notes_defendant_sentences.*.decided_matter_ids.*' => ['integer', 'exists:matter_categories,id'],
            'notes_defendant_sentences.*.punishments' => ['nullable', 'array'],
            'notes_defendant_sentences.*.allocations' => ['nullable', 'array'],
            'notes_defendant_sentences.*.allocations.*.matter_category_id' => ['nullable', 'integer', 'exists:matter_categories,id'],
            'notes_defendant_sentences.*.allocations.*.punishments' => ['nullable', 'array'],
            'notes_handover_issued' => ['nullable', 'boolean'],
            'clerk_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'notes_decision_status.required' => 'Шүүх хуралдааны шийдвэрийг заавал сонгоно уу.',
            'clerk_id.required' => 'Шүүх хуралдааны нарийн бичгийг заавал сонгоно уу.',
        ]);

        // Secretary мөн нарийн бичиг, тэмдэглэл гаргасан эсэхийг тэмдэглэж чадна (таны шаардлагаар адилхан)
        $hearing->clerk_id = $data['clerk_id'] ?? null;
        if (($data['clerk_id'] ?? null) !== $oldClerkId) {
            $hearing->notes_clerk_selected_at = ! empty($data['clerk_id']) ? now() : null;
        }
        $issued = (bool) ($data['notes_handover_issued'] ?? false);
        $hearing->notes_handover_issued = $issued;
        if ($issued && ! $oldIssued) {
            $hearing->notes_handover_issued_at = now();
            $hearing->notes_handover_saved_at = now();
        } elseif (! $issued) {
            $hearing->notes_handover_issued_at = null;
        }

        $hearing->notes_handover_text = $data['notes_handover_text'] ?? $hearing->notes_handover_text;

        $normalizedDefendantSentences = $this->normalizeDefendantSentences($data['notes_defendant_sentences'] ?? []);
        if (($data['notes_decision_status'] ?? '') === 'Шийдвэрлэсэн') {
            if (empty($normalizedDefendantSentences)) {
                throw ValidationException::withMessages([
                    'notes_defendant_sentences' => 'Шийдвэрлэсэн үед шүүгдэгч тус бүрийн шийтгэлийн мэдээллийг оруулна уу.',
                ]);
            }
            foreach ($normalizedDefendantSentences as $index => $sentence) {
                if (empty($sentence['defendant_name'])) {
                    throw ValidationException::withMessages([
                        "notes_defendant_sentences.{$index}.defendant_name" => 'Шүүгдэгчийн нэр шаардлагатай.',
                    ]);
                }
                if (empty($sentence['decided_matter_ids']) && empty($sentence['allocations'])) {
                    throw ValidationException::withMessages([
                        "notes_defendant_sentences.{$index}.allocations" => 'Шийдвэрлэсэн зүйл ангийг C хувилбарын мөрөөс сонгоно уу.',
                    ]);
                }
                if (empty($sentence['punishments']) && empty($sentence['allocations'])) {
                    throw ValidationException::withMessages([
                        "notes_defendant_sentences.{$index}.allocations" => 'Ялын төрлийг C хувилбараар хуваарилж оруулна уу.',
                    ]);
                }
            }
        } else {
            $normalizedDefendantSentences = [];
        }

        if (! empty($normalizedDefendantSentences)) {
            $firstSentence = $normalizedDefendantSentences[0];
            $firstMatterIds = $firstSentence['decided_matter_ids'] ?? [];
            if (empty($firstMatterIds)) {
                $firstMatterIds = collect($firstSentence['allocations'] ?? [])
                    ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
            }
            $matterNames = MatterCategory::whereIn('id', $firstMatterIds)->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = ! empty($matterNames) ? implode(', ', $matterNames) : null;
            $firstFine = $firstSentence['punishments']['fine'] ?? collect($firstSentence['allocations'] ?? [])
                ->map(fn ($row) => is_array($row) ? ($row['punishments']['fine'] ?? null) : null)
                ->filter()
                ->first();
            $hearing->notes_fine_units = (string) ($firstFine['fine_units'] ?? '');
            $hearing->notes_damage_amount = (string) ($firstFine['damage_amount'] ?? '');
        } elseif (! empty($data['notes_decided_matter_ids']) && is_array($data['notes_decided_matter_ids'])) {
            $ids = array_map('intval', $data['notes_decided_matter_ids']);
            $names = MatterCategory::whereIn('id', $ids)->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = $names ? implode(', ', $names) : null;
            $hearing->notes_fine_units = (string) $this->parseGroupedNumber($data['notes_fine_units'] ?? '');
            $hearing->notes_damage_amount = (string) $this->parseGroupedNumber($data['notes_damage_amount'] ?? '');
        } else {
            $originalNames = $hearing->matterCategories()->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = ! empty($originalNames) ? implode(', ', $originalNames) : null;
            $hearing->notes_fine_units = (string) $this->parseGroupedNumber($data['notes_fine_units'] ?? '');
            $hearing->notes_damage_amount = (string) $this->parseGroupedNumber($data['notes_damage_amount'] ?? '');
        }
        $hearing->notes_defendant_sentences = $normalizedDefendantSentences;
        $hearing->notes_decision_status = $data['notes_decision_status'] ?? $hearing->notes_decision_status;

        $hearing->save();

        return back()->with('success', 'Тэмдэглэл амжилттай хадгаллаа.');
    }

    private function parseGroupedNumber(mixed $value): int
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' || $digits === null ? 0 : (int) $digits;
    }

    /**
     * @param  array<int, mixed>  $sentences
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDefendantSentences(array $sentences): array
    {
        $result = [];
        foreach ($sentences as $sentence) {
            if (! is_array($sentence)) {
                continue;
            }

            $defendantName = trim((string) ($sentence['defendant_name'] ?? ''));
            $decidedMatterIds = array_values(array_filter(array_map('intval', (array) ($sentence['decided_matter_ids'] ?? []))));
            $punishmentsRaw = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
            $punishments = $this->normalizePunishments($punishmentsRaw, true);

            if ($defendantName === '' && empty($decidedMatterIds) && empty($punishments) && empty($sentence['allocations'])) {
                continue;
            }

            $allocations = $this->normalizeSentenceAllocations(
                is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [],
                $decidedMatterIds,
                $punishments
            );
            if (empty($decidedMatterIds) && ! empty($allocations)) {
                $decidedMatterIds = collect($allocations)
                    ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
            }

            $result[] = [
                'defendant_name' => $defendantName,
                'decided_matter_ids' => $decidedMatterIds,
                'punishments' => $punishments,
                'allocations' => $allocations,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $allocationsRaw
     * @param  array<int, int>  $decidedMatterIds
     * @param  array<string, mixed>  $fallbackPunishments
     * @return array<int, array{matter_category_id:int,punishments:array<string,mixed>}>
     */
    private function normalizeSentenceAllocations(array $allocationsRaw, array $decidedMatterIds, array $fallbackPunishments): array
    {
        $result = [];
        foreach ($allocationsRaw as $allocation) {
            if (! is_array($allocation)) {
                continue;
            }
            $matterCategoryId = (int) ($allocation['matter_category_id'] ?? 0);
            if ($matterCategoryId < 1 || ! in_array($matterCategoryId, $decidedMatterIds, true)) {
                continue;
            }
            $punishmentsRaw = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
            $punishments = $this->normalizePunishments($punishmentsRaw, false);
            if (empty($punishments)) {
                continue;
            }
            $result[] = ['matter_category_id' => $matterCategoryId, 'punishments' => $punishments];
        }
        if (! empty($result)) {
            $byMatter = [];
            foreach ($result as $row) {
                $byMatter[$row['matter_category_id']] = $row;
            }

            return array_values($byMatter);
        }
        if (count($decidedMatterIds) === 1 && ! empty($fallbackPunishments)) {
            return [[
                'matter_category_id' => $decidedMatterIds[0],
                'punishments' => $fallbackPunishments,
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $punishmentsRaw
     * @return array<string, mixed>
     */
    private function normalizePunishments(array $punishmentsRaw, bool $requireEnabledFlag = true): array
    {
        $punishments = [];
        if ((! $requireEnabledFlag || ! empty($punishmentsRaw['fine']['enabled'] ?? null)) && is_array($punishmentsRaw['fine'] ?? null)) {
            $punishments['fine'] = [
                'fine_units' => $this->parseGroupedNumber($punishmentsRaw['fine']['fine_units'] ?? ''),
                'damage_amount' => $this->parseGroupedNumber($punishmentsRaw['fine']['damage_amount'] ?? ''),
            ];
        }
        if ((! $requireEnabledFlag || ! empty($punishmentsRaw['community_service']['enabled'] ?? null)) && is_array($punishmentsRaw['community_service'] ?? null)) {
            $hoursRaw = preg_replace('/\D+/', '', (string) ($punishmentsRaw['community_service']['hours'] ?? '0'));
            $hours = (int) ($hoursRaw === '' ? '0' : $hoursRaw);
            if (strlen((string) $hours) > 3 || $hours > 720) {
                throw ValidationException::withMessages([
                    'notes_defendant_sentences' => 'Нийтэд тустай ажил 720 цагаас ихгүй, 3 оронтой тоо байна.',
                ]);
            }
            $punishments['community_service'] = ['hours' => max(0, $hours)];
        }
        foreach (['travel_restriction', 'imprisonment_open', 'imprisonment_closed', 'rights_ban_public_service', 'rights_ban_professional_activity', 'rights_ban_driving'] as $key) {
            if ((! $requireEnabledFlag || ! empty($punishmentsRaw[$key]['enabled'] ?? null)) && is_array($punishmentsRaw[$key] ?? null)) {
                $years = max(0, (int) ($punishmentsRaw[$key]['years'] ?? 0));
                $months = (int) ($punishmentsRaw[$key]['months'] ?? 0);
                if ($months < 0 || $months > 12) {
                    throw ValidationException::withMessages([
                        'notes_defendant_sentences' => 'Сар 0-12 хооронд байна.',
                    ]);
                }
                $punishments[$key] = ['years' => $years, 'months' => $months];
            }
        }

        return $punishments;
    }

    public function reschedule(Hearing $hearing)
    {
        if ((int) $hearing->created_by !== (int) auth()->id()) {
            abort(403, 'Та зөвхөн өөрийн оруулсан хурлын тэмдэглэлийг шинэчилж чадна.');
        }

        $judgeIds = $hearing->judgeSlotUserIdsOrdered();

        $normalizeTextList = function (?string $value): array {
            if (! is_string($value) || trim($value) === '') {
                return [];
            }

            return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/u', $value))));
        };

        $defendantNames = is_array($hearing->defendant_names) && ! empty($hearing->defendant_names)
            ? array_values(array_filter($hearing->defendant_names))
            : $normalizeTextList($hearing->defendants);
        $preventiveMeasure = is_array($hearing->preventive_measure)
            ? $hearing->preventive_measure
            : $normalizeTextList(is_string($hearing->preventive_measure) ? $hearing->preventive_measure : null);
        $judgeNamesText = trim((string) ($hearing->judge_names_text ?? ''));
        if ($judgeNamesText === '') {
            $judgeNamesText = $hearing->judges()->orderBy('hearing_judges.position')->pluck('users.name')->implode(', ');
        }

        $prefill = [
            'case_no' => $hearing->case_no,
            'title' => $hearing->title,
            'hearing_state' => $hearing->hearing_state,
            'hearing_type' => $hearing->hearing_type,
            'hearing_date' => optional($hearing->hearing_date)->format('Y-m-d'),
            'reschedule_source_hearing_id' => (string) $hearing->id,
            'hour' => $hearing->hour,
            'minute' => $hearing->minute,
            'courtroom' => $hearing->courtroom,
            'presiding_judge_id' => $judgeIds[0] ?? '',
            'member_judge_1_id' => $judgeIds[1] ?? '',
            'member_judge_2_id' => $judgeIds[2] ?? '',
            'judge_names_text' => $judgeNamesText,
            'defendant_names' => $defendantNames,
            'prosecutor_ids' => $hearing->prosecutor_ids_list,
            'matter_category_ids' => $hearing->matter_category_ids ?? [],
            'preventive_measure' => $preventiveMeasure,
            'defendant_lawyers_text' => $hearing->defendant_lawyers_text ?? [],
            'victim_lawyers_text' => $hearing->victim_lawyers_text ?? [],
            'victim_legal_rep_lawyers_text' => $hearing->victim_legal_rep_lawyers_text ?? [],
            'civil_plaintiff_lawyers' => $hearing->civil_plaintiff_lawyers ?? [],
            'civil_defendant_lawyers' => $hearing->civil_defendant_lawyers ?? [],
            'victim_names' => $normalizeTextList($hearing->victim_name),
            'victim_legal_rep_names' => $normalizeTextList($hearing->victim_legal_rep),
            'witness_names' => $normalizeTextList($hearing->witnesses),
            'expert_names' => $normalizeTextList($hearing->experts),
            'civil_plaintiff_names' => $normalizeTextList($hearing->civil_plaintiff),
            'civil_defendant_names' => $normalizeTextList($hearing->civil_defendant),
            'note' => $hearing->note,
        ];

        return redirect()
            ->route('secretary.hearings.create', [
                'presiding_judge_id' => $prefill['presiding_judge_id'] ?? '',
                'member_judge_1_id' => $prefill['member_judge_1_id'] ?? '',
                'member_judge_2_id' => $prefill['member_judge_2_id'] ?? '',
                'reschedule_source_hearing_id' => $prefill['reschedule_source_hearing_id'] ?? '',
                'judge_names_text' => $prefill['judge_names_text'] ?? '',
            ])
            ->with('reschedule_prefill', $prefill)
            ->withInput($prefill);
    }
}
