<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Concerns\ManagesHearingLogic;
use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Services\Notifications\HearingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HearingsController extends Controller
{
    use ManagesHearingLogic;

    public function index(Request $request)
    {
        // Огноо → цаг → танхимаар эрэмбэлнэ (админтай адил)
        $query = Hearing::with(['judges', 'prosecutor'])
            ->where('created_by', auth()->id())
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        // Хайлт
        if ($request->filled('q')) {
            $q = '%'.$request->input('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('case_no', 'like', $q)
                    ->orWhere('courtroom', 'like', $q)
                    ->orWhere('defendants', 'like', $q);
            });
        }
        if ($request->filled('hearing_date')) {
            $query->whereDate('hearing_date', $request->input('hearing_date'));
        }
        if ($request->filled('courtroom')) {
            $query->where('courtroom', $request->input('courtroom'));
        }

        $hearings = $query->paginate(20)->withQueryString();

        return view('hearings.index', [
            'hearings' => $hearings,
            'indexType' => 'secretary',
            'headerTitle' => 'Хурлын зар (Шүүгчийн туслах)',
            'listTitle' => 'Миний хурлын зарууд',
            'createUrl' => route('secretary.hearings.create'),
            'createLabel' => 'Хурлын зар оруулах',
            'searchUrl' => route('secretary.hearings.index'),
            'courtrooms' => $this->allowedCourtrooms(),
        ]);
    }

    public function create()
    {
        $reschedulePrefill = (array) session('reschedule_prefill', []);
        $prefillPresidingJudgeId = (string) request()->query('presiding_judge_id', $reschedulePrefill['presiding_judge_id'] ?? '');
        $prefillMemberJudge1Id = (string) request()->query('member_judge_1_id', $reschedulePrefill['member_judge_1_id'] ?? '');
        $prefillMemberJudge2Id = (string) request()->query('member_judge_2_id', $reschedulePrefill['member_judge_2_id'] ?? '');
        $prefillSourceHearingId = (string) request()->query('reschedule_source_hearing_id', $reschedulePrefill['reschedule_source_hearing_id'] ?? '');
        $prefillJudgeNamesText = (string) request()->query('judge_names_text', $reschedulePrefill['judge_names_text'] ?? '');
        $resolvedPresidingJudgeId = request()->has('presiding_judge_id') ? $prefillPresidingJudgeId : (string) old('presiding_judge_id', $prefillPresidingJudgeId);
        $resolvedMemberJudge1Id = request()->has('member_judge_1_id') ? $prefillMemberJudge1Id : (string) old('member_judge_1_id', $prefillMemberJudge1Id);
        $resolvedMemberJudge2Id = request()->has('member_judge_2_id') ? $prefillMemberJudge2Id : (string) old('member_judge_2_id', $prefillMemberJudge2Id);

        if ($prefillSourceHearingId !== '' && ctype_digit($prefillSourceHearingId)) {
            $sourceHearing = Hearing::query()->whereKey((int) $prefillSourceHearingId)->where('created_by', auth()->id())->first();
            if ($sourceHearing) {
                $sourceJudgeIds = $sourceHearing->judgeSlotUserIdsOrdered();
                $resolvedPresidingJudgeId = (string) ($sourceJudgeIds[0] ?? $resolvedPresidingJudgeId);
                $resolvedMemberJudge1Id = (string) ($sourceJudgeIds[1] ?? $resolvedMemberJudge1Id);
                $resolvedMemberJudge2Id = (string) ($sourceJudgeIds[2] ?? $resolvedMemberJudge2Id);
            }
        }

        $selectedJudgeIds = collect([
            $resolvedPresidingJudgeId,
            $resolvedMemberJudge1Id,
            $resolvedMemberJudge2Id,
        ])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($selectedJudgeIds->isEmpty()) {
            $selectedJudgeIds = $this->inferJudgeIdsFromText((string) old('judge_names_text', $prefillJudgeNamesText));
        }
        $judges = $this->judgesForHearingForm($selectedJudgeIds);
        $prosecutors = User::role('prosecutor')->where('is_active', true)->orderBy('name')->get();
        $lawyers = User::role('lawyer')->where('is_active', true)->orderBy('name')->get();
        $matterCategories = MatterCategory::orderBy('sort_order')->orderBy('name')->get();

        $vars = [
            'formAction' => route('secretary.hearings.store'),
            'byDateUrl' => route('secretary.hearings.byDate'),
            'editBaseUrl' => url('/secretary/hearings'),
            'checkConflictUrl' => route('secretary.hearings.checkConflict'),
            'backUrl' => route('secretary.hearings.index'),
            'defendantSearchUrl' => route('secretary.defendant-search'),
            'prefillPresidingJudgeId' => $prefillPresidingJudgeId,
            'prefillMemberJudge1Id' => $prefillMemberJudge1Id,
            'prefillMemberJudge2Id' => $prefillMemberJudge2Id,
            'prefillSourceHearingId' => $prefillSourceHearingId,
            'resolvedPresidingJudgeId' => $resolvedPresidingJudgeId,
            'resolvedMemberJudge1Id' => $resolvedMemberJudge1Id,
            'resolvedMemberJudge2Id' => $resolvedMemberJudge2Id,
        ];

        return view('hearings.create', array_merge(compact('judges', 'prosecutors', 'lawyers', 'matterCategories'), $vars));
    }

    /** Тухайн өдрийн хуралууд (JSON) — create хуудсын хажуу талд харуулах. */
    public function byDate(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
            'ignore_id' => ['nullable', 'integer'],
        ]);
        $date = $request->input('date');
        $ignoreId = $request->integer('ignore_id');

        $hearings = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->whereDate('start_at', $date)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->orderBy('start_at')
            ->orderBy('courtroom')
            ->get();

        return response()->json(
            $hearings->map(function ($h) {
                $prosecutorName = $h->relationLoaded('prosecutor') && $h->prosecutor
                    ? $h->prosecutor->name
                    : '—';

                $def = collect($h->defendant_lawyers_text ?? [])->filter()->values()->all();
                $victim = collect($h->victim_lawyers_text ?? [])->filter()->values()->all();
                $victimRep = collect($h->victim_legal_rep_lawyers_text ?? [])->filter()->values()->all();
                $civilPl = collect($h->civil_plaintiff_lawyers ?? [])->filter()->values()->all();
                $civilDef = collect($h->civil_defendant_lawyers ?? [])->filter()->values()->all();

                $allLawyers = collect([$def, $victim, $victimRep, $civilPl, $civilDef])->flatten()->unique()->values()->all();
                $lawyer_names = count($allLawyers) > 0 ? implode(', ', $allLawyers) : '—';

                return [
                    'id' => $h->id,
                    'start_time' => optional($h->start_at)->format('H:i'),
                    'courtroom_label' => $h->courtroom ? $h->courtroom.' заал' : '—',
                    'judge_names' => $h->relationLoaded('judges') ? $h->judges->pluck('name')->implode(', ') : '—',
                    'prosecutor_name' => $prosecutorName,
                    'lawyer_names' => $lawyer_names,
                    'defendant_lawyers_text' => count($def) ? implode(', ', $def) : null,
                    'victim_lawyers_text' => count($victim) ? implode(', ', $victim) : null,
                    'victim_legal_rep_lawyers_text' => count($victimRep) ? implode(', ', $victimRep) : null,
                    'civil_plaintiff_lawyers' => count($civilPl) ? implode(', ', $civilPl) : null,
                    'civil_defendant_lawyers' => count($civilDef) ? implode(', ', $civilDef) : null,
                    'state' => $h->hearing_state ?? $h->title ?? '—',
                ];
            })
        );
    }

    public function edit(Hearing $hearing)
    {
        abort_unless((int) $hearing->created_by === (int) auth()->id(), 403);

        $hearing->load('judges');
        $selectedJudgeIds = $hearing->judges->pluck('id')->map(fn ($id) => (int) $id)->values();
        if ($hearing->judge_id) {
            $selectedJudgeIds->push((int) $hearing->judge_id);
        }
        if ($selectedJudgeIds->isEmpty() && ! empty($hearing->judge_names_text)) {
            $judgeNames = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/u', (string) $hearing->judge_names_text))));
            if (! empty($judgeNames)) {
                $fallbackIds = User::query()
                    ->whereIn('name', $judgeNames)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                foreach ($fallbackIds as $id) {
                    $selectedJudgeIds->push($id);
                }
            }
        }
        $selectedJudgeIds = $selectedJudgeIds->unique()->values();

        $judges = $this->judgesForHearingForm($selectedJudgeIds);
        $prosecutors = User::role('prosecutor')->where('is_active', true)->orderBy('name')->get();
        $lawyers = User::role('lawyer')->where('is_active', true)->orderBy('name')->get();
        $matterCategories = MatterCategory::orderBy('sort_order')->orderBy('name')->get();

        $vars = [
            'formAction' => route('secretary.hearings.update', $hearing),
            'backUrl' => route('secretary.hearings.index'),
            'checkConflictUrl' => route('secretary.hearings.checkConflict'),
            'byDateUrl' => route('secretary.hearings.byDate'),
            'editBaseUrl' => url('/secretary/hearings'),
            'headerTitle' => 'Хурлын зар засварлах (Шүүгчийн туслах)',
            'defendantSearchUrl' => route('secretary.defendant-search'),
        ];

        return view('hearings.edit', array_merge(compact('hearing', 'judges', 'prosecutors', 'lawyers', 'matterCategories'), $vars));
    }

    private function parseTextToArray(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $text))));
    }

    public function store(Request $request)
    {
        $data = $this->validateHearing($request);
        $data = $this->normalizeFormData($data);

        if (! empty($data['prosecutor_id'])) {
            $this->assertProsecutorRole($data['prosecutor_id']);
        }

        $judgeIds = $this->normalizeJudgeIds($request);
        $this->assertOneOrThreeJudges($judgeIds);

        $duration = $this->computeDurationMinutes($judgeIds);
        [$start, $end] = $this->buildStartEnd(
            $data['hearing_date'],
            (int) $data['hour'],
            (int) $data['minute'],
            $duration
        );

        $lawyerNames = array_values(array_filter(array_unique(array_merge(
            $data['defendant_lawyers_text'] ?? [],
            $data['victim_lawyers_text'] ?? [],
            $data['victim_legal_rep_lawyers_text'] ?? [],
            $data['civil_plaintiff_lawyers'] ?? [],
            $data['civil_defendant_lawyers'] ?? []
        ))));

        $prosecutorIds = array_values(array_filter(array_unique(array_map('intval', $data['prosecutor_ids'] ?? []))));
        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, null, $prosecutorIds);

        $hearing = DB::transaction(function () use ($request, $data, $start, $end, $duration) {
            $hearing = Hearing::create([
                'created_by' => auth()->id(),
                'case_no' => $data['case_no'] ?? null,
                'title' => $data['title'],
                'hearing_state' => $data['hearing_state'] ?? 'Хэвийн',
                'hearing_type' => $data['hearing_type'] ?? null,
                'hearing_date' => $data['hearing_date'],
                'hour' => (int) $data['hour'],
                'minute' => (int) $data['minute'],
                'start_at' => $start,
                'end_at' => $end,
                'duration_minutes' => $duration,
                'courtroom' => $data['courtroom'],
                'preventive_measure' => ! empty($data['preventive_measure'])
                    ? implode(', ', array_filter($data['preventive_measure']))
                    : null,
                'prosecutor_id' => $data['prosecutor_id'] ?? null,
                'defendant_names' => array_values(array_filter($data['defendant_names'] ?? [])),
                'defendant_registries' => array_values($data['defendant_registries'] ?? []),
                'victim_registries' => array_values($data['victim_registries'] ?? []),
                'victim_legal_rep_registries' => array_values($data['victim_legal_rep_registries'] ?? []),
                'witness_registries' => array_values($data['witness_registries'] ?? []),
                'civil_plaintiff_registries' => array_values($data['civil_plaintiff_registries'] ?? []),
                'civil_defendant_registries' => array_values($data['civil_defendant_registries'] ?? []),
                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'civil_plaintiff_lawyers' => array_values(array_filter($data['civil_plaintiff_lawyers'] ?? [])),
                'civil_defendant_lawyers' => array_values(array_filter($data['civil_defendant_lawyers'] ?? [])),
                'matter_category_ids' => array_values(array_filter(array_map('intval', $data['matter_category_ids'] ?? []))),
                'victim_name' => $this->namesArrayToText($data['victim_names'] ?? []),
                'victim_legal_rep' => $this->namesArrayToText($data['victim_legal_rep_names'] ?? []),
                'witnesses' => $this->namesArrayToText($data['witness_names'] ?? []),
                'experts' => $data['experts'] ?? null,
                'civil_plaintiff' => $this->namesArrayToText($data['civil_plaintiff_names'] ?? []),
                'civil_defendant' => $this->namesArrayToText($data['civil_defendant_names'] ?? []),
                'note' => $data['note'] ?? null,
                'status' => 'scheduled',
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return $hearing->fresh();
        });

        $this->dispatchHearingNotification($hearing, 'created');

        return redirect()->route('secretary.hearings.index')->with('success', 'Хурлын зар бүртгэлээ.');
    }

    private function normalizeFormData(array $data): array
    {
        if (empty($data['defendant_names']) && ! empty($data['defendants'] ?? '')) {
            $data['defendant_names'] = $this->parseTextToArray($data['defendants']);
        }
        if (empty($data['defendant_names']) && ! empty($data['defendant_names_text'] ?? '')) {
            $data['defendant_names'] = $this->parseTextToArray($data['defendant_names_text']);
        }
        $rawDefendantRegistries = $data['defendant_registries'] ?? [];
        if (! is_array($rawDefendantRegistries)) {
            $rawDefendantRegistries = [];
        }
        $data['defendant_registries'] = collect($data['defendant_names'] ?? [])
            ->values()
            ->map(function ($_, $index) use ($rawDefendantRegistries) {
                return mb_strtoupper(trim((string) ($rawDefendantRegistries[$index] ?? '')), 'UTF-8');
            })
            ->all();
        $textToNames = function (string $key, string $fallback) use ($data): array {
            $arr = $data[$key] ?? [];
            if (is_array($arr) && ! empty($arr)) {
                return array_values(array_filter(array_map('trim', $arr)));
            }

            return $this->parseTextToArray((string) ($data[$fallback] ?? ''));
        };
        $mapNamesWithRegistries = function (array $names, string $registryKey) use ($data): array {
            $rawRegistries = $data[$registryKey] ?? [];
            if (! is_array($rawRegistries)) {
                $rawRegistries = [];
            }

            return collect($names)
                ->values()
                ->map(function ($_, $index) use ($rawRegistries) {
                    return mb_strtoupper(trim((string) ($rawRegistries[$index] ?? '')), 'UTF-8');
                })
                ->all();
        };
        $data['victim_names'] = $textToNames('victim_names', 'victim_name');
        $data['victim_legal_rep_names'] = $textToNames('victim_legal_rep_names', 'victim_legal_rep');
        $data['witness_names'] = $textToNames('witness_names', 'witnesses');
        $data['civil_plaintiff_names'] = $textToNames('civil_plaintiff_names', 'civil_plaintiff');
        $data['civil_defendant_names'] = $textToNames('civil_defendant_names', 'civil_defendant');
        $data['victim_registries'] = $mapNamesWithRegistries((array) ($data['victim_names'] ?? []), 'victim_registries');
        $data['victim_legal_rep_registries'] = $mapNamesWithRegistries((array) ($data['victim_legal_rep_names'] ?? []), 'victim_legal_rep_registries');
        $data['witness_registries'] = $mapNamesWithRegistries((array) ($data['witness_names'] ?? []), 'witness_registries');
        $data['civil_plaintiff_registries'] = $mapNamesWithRegistries((array) ($data['civil_plaintiff_names'] ?? []), 'civil_plaintiff_registries');
        $data['civil_defendant_registries'] = $mapNamesWithRegistries((array) ($data['civil_defendant_names'] ?? []), 'civil_defendant_registries');
        if (empty($data['defendant_lawyers_text']) && ! empty($data['defendant_lawyers_text_str'] ?? '')) {
            $data['defendant_lawyers_text'] = $this->parseTextToArray($data['defendant_lawyers_text_str']);
        }
        if (empty($data['victim_lawyers_text']) && ! empty($data['victim_lawyers_text_str'] ?? '')) {
            $data['victim_lawyers_text'] = $this->parseTextToArray($data['victim_lawyers_text_str']);
        }
        if (empty($data['victim_legal_rep_lawyers_text']) && ! empty($data['victim_legal_rep_lawyers_text_str'] ?? '')) {
            $data['victim_legal_rep_lawyers_text'] = $this->parseTextToArray($data['victim_legal_rep_lawyers_text_str']);
        }

        return $data;
    }

    private function namesArrayToText(array $names): string
    {
        return implode("\n", array_values(array_filter($names)));
    }

    public function update(Request $request, Hearing $hearing)
    {
        abort_unless((int) $hearing->created_by === (int) auth()->id(), 403);

        $data = $this->validateHearing($request);
        $data = $this->normalizeFormData($data);

        if (! empty($data['prosecutor_id'])) {
            $this->assertProsecutorRole($data['prosecutor_id']);
        }

        $judgeIds = $this->normalizeJudgeIds($request);
        $this->assertOneOrThreeJudges($judgeIds);

        $duration = $this->computeDurationMinutes($judgeIds);
        [$start, $end] = $this->buildStartEnd(
            $data['hearing_date'],
            (int) $data['hour'],
            (int) $data['minute'],
            $duration
        );

        $lawyerNames = array_values(array_filter(array_unique(array_merge(
            $data['defendant_lawyers_text'] ?? [],
            $data['victim_lawyers_text'] ?? [],
            $data['victim_legal_rep_lawyers_text'] ?? [],
            $data['civil_plaintiff_lawyers'] ?? [],
            $data['civil_defendant_lawyers'] ?? []
        ))));

        $prosecutorIds = array_values(array_filter(array_unique(array_map('intval', $data['prosecutor_ids'] ?? []))));
        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, $hearing->id, $prosecutorIds);

        $hearing = DB::transaction(function () use ($request, $data, $hearing, $start, $end, $duration) {
            $hearing->update([
                'case_no' => $data['case_no'] ?? null,
                'title' => $data['title'],
                'hearing_state' => $data['hearing_state'] ?? 'Хэвийн',
                'hearing_type' => $data['hearing_type'] ?? null,
                'hearing_date' => $data['hearing_date'],
                'hour' => (int) $data['hour'],
                'minute' => (int) $data['minute'],
                'start_at' => $start,
                'end_at' => $end,
                'duration_minutes' => $duration,
                'courtroom' => $data['courtroom'],
                'preventive_measure' => ! empty($data['preventive_measure'])
                    ? implode(', ', array_filter($data['preventive_measure']))
                    : null,
                'prosecutor_id' => $data['prosecutor_id'] ?? null,
                'defendant_names' => array_values(array_filter($data['defendant_names'] ?? [])),
                'defendant_registries' => array_values($data['defendant_registries'] ?? []),
                'victim_registries' => array_values($data['victim_registries'] ?? []),
                'victim_legal_rep_registries' => array_values($data['victim_legal_rep_registries'] ?? []),
                'witness_registries' => array_values($data['witness_registries'] ?? []),
                'civil_plaintiff_registries' => array_values($data['civil_plaintiff_registries'] ?? []),
                'civil_defendant_registries' => array_values($data['civil_defendant_registries'] ?? []),
                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'civil_plaintiff_lawyers' => array_values(array_filter($data['civil_plaintiff_lawyers'] ?? [])),
                'civil_defendant_lawyers' => array_values(array_filter($data['civil_defendant_lawyers'] ?? [])),
                'matter_category_ids' => array_values(array_filter(array_map('intval', $data['matter_category_ids'] ?? []))),
                'victim_name' => $this->namesArrayToText($data['victim_names'] ?? []),
                'victim_legal_rep' => $this->namesArrayToText($data['victim_legal_rep_names'] ?? []),
                'witnesses' => $this->namesArrayToText($data['witness_names'] ?? []),
                'experts' => $data['experts'] ?? null,
                'civil_plaintiff' => $this->namesArrayToText($data['civil_plaintiff_names'] ?? []),
                'civil_defendant' => $this->namesArrayToText($data['civil_defendant_names'] ?? []),
                'note' => $data['note'] ?? null,
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return $hearing->fresh();
        });

        $this->dispatchHearingNotification($hearing, 'updated');

        return redirect()->route('secretary.hearings.index')->with('success', 'Хурлын зар шинэчлэгдлээ.');
    }

    /**
     * Хурлын зарыг устгах (зөвхөн өөрийн оруулсан)
     */
    public function destroy(Hearing $hearing)
    {
        abort_unless((int) $hearing->created_by === (int) auth()->id(), 403, 'Та зөвхөн өөрийн оруулсан хурлын зарыг устгах эрхтэй.');
        $hearing->judges()->detach();
        $hearing->delete();

        return redirect()->route('secretary.hearings.index')->with('success', 'Хурлын зар устгагдлаа.');
    }

    public function checkConflict(Request $request)
    {
        $data = $request->validate([
            'hearing_date' => ['required', 'date'],
            'hour' => ['required', 'integer', 'min:8', 'max:18'],
            'minute' => ['required', 'integer', Rule::in($this->allowedMinutes())],
            'courtroom' => ['required', 'string', Rule::in($this->allowedCourtrooms())],
            'presiding_judge_id' => ['required', 'integer', 'exists:users,id'],
            'member_judge_1_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_judge_2_id' => ['nullable', 'integer', 'exists:users,id'],
            'ignore_id' => ['nullable', 'integer'],
            'prosecutor_ids' => ['nullable', 'array'],
            'prosecutor_ids.*' => ['nullable', 'integer', 'exists:users,id'],
            'defendant_lawyers_text' => ['nullable', 'array'],
            'defendant_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_lawyers_text' => ['nullable', 'array'],
            'victim_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_lawyers_text' => ['nullable', 'array'],
            'victim_legal_rep_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'civil_plaintiff_lawyers' => ['nullable', 'array'],
            'civil_plaintiff_lawyers.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_lawyers' => ['nullable', 'array'],
            'civil_defendant_lawyers.*' => ['nullable', 'string', 'max:255'],
        ]);

        $judgeIds = $this->normalizeJudgeIds($request);

        if (count($judgeIds) === 2) {
            return response()->json([
                'ok' => false,
                'field' => 'member_judge_1_id',
                'message' => '2 шүүгчтэй хурал зөвшөөрөгдөхгүй. 1 эсвэл 3 шүүгч сонгоно.',
            ]);
        }

        $duration = $this->computeDurationMinutes($judgeIds);
        [$start, $end] = $this->buildStartEnd(
            $data['hearing_date'],
            (int) $data['hour'],
            (int) $data['minute'],
            $duration
        );

        $lawyerNames = array_values(array_filter(array_unique(array_merge(
            $data['defendant_lawyers_text'] ?? [],
            $data['victim_lawyers_text'] ?? [],
            $data['victim_legal_rep_lawyers_text'] ?? [],
            $data['civil_plaintiff_lawyers'] ?? [],
            $data['civil_defendant_lawyers'] ?? []
        ))));

        try {
            $prosecutorIds = array_values(array_filter(array_unique(array_map('intval', $data['prosecutor_ids'] ?? []))));
            $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, $data['ignore_id'] ?? null, $prosecutorIds);

            return response()->json(['ok' => true]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $field = collect(array_keys($errors))->first();
            $msg = collect($errors)->flatten()->first() ?? 'Давхцал илэрлээ.';

            return response()->json(['ok' => false, 'field' => $field, 'message' => $msg]);
        }
    }

    private function dispatchHearingNotification(Hearing $hearing, string $action): void
    {
        app(HearingNotificationService::class)->send($hearing, $action);
    }

    private function validateHearing(Request $request): array
    {
        $data = $request->validate([
            'case_no' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'hearing_state' => ['required', 'string', Rule::in($this->allowedHearingStates())],
            'hearing_type' => ['nullable', 'string', Rule::in(['Анхан шат', 'Урьдчилсан хэлэлцүүлэг'])],
            'hearing_date' => ['required', 'date'],
            'hour' => ['required', 'integer', 'min:8', 'max:18'],
            'minute' => ['required', 'integer', Rule::in($this->allowedMinutes())],
            'courtroom' => ['required', 'string', Rule::in($this->allowedCourtrooms())],
            'presiding_judge_id' => ['required', 'integer', 'exists:users,id'],
            'member_judge_1_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_2_id'],
            'member_judge_2_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_1_id'],
            'defendant_names' => ['required', 'array', 'min:1'],
            'defendant_names.*' => ['required', 'string', 'max:255'],
            'defendant_registries' => ['nullable', 'array'],
            'defendant_registries.*' => ['nullable', 'string', 'max:20'],
            'defendant_names_text' => ['nullable', 'string'],
            'defendants' => ['nullable', 'string'],
            'victim_names' => ['nullable', 'array'],
            'victim_names.*' => ['nullable', 'string', 'max:255'],
            'victim_registries' => ['nullable', 'array'],
            'victim_registries.*' => ['nullable', 'string', 'max:20'],
            'victim_legal_rep_names' => ['nullable', 'array'],
            'victim_legal_rep_names.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_registries' => ['nullable', 'array'],
            'victim_legal_rep_registries.*' => ['nullable', 'string', 'max:20'],
            'witness_names' => ['nullable', 'array'],
            'witness_names.*' => ['nullable', 'string', 'max:255'],
            'witness_registries' => ['nullable', 'array'],
            'witness_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_plaintiff_names' => ['nullable', 'array'],
            'civil_plaintiff_names.*' => ['nullable', 'string', 'max:255'],
            'civil_plaintiff_registries' => ['nullable', 'array'],
            'civil_plaintiff_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_defendant_names' => ['nullable', 'array'],
            'civil_defendant_names.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_registries' => ['nullable', 'array'],
            'civil_defendant_registries.*' => ['nullable', 'string', 'max:20'],
            'preventive_measure' => ['required', 'array', 'min:1'],
            'preventive_measure.*' => ['required', 'string', Rule::in($this->allowedPreventiveMeasures())],
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['required', 'integer', 'exists:users,id'],
            'prosecutor_id' => ['nullable', 'integer', 'exists:users,id'],
            'defendant_lawyers_text' => ['nullable', 'array'],
            'defendant_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'defendant_lawyers_text_str' => ['nullable', 'string'],
            'victim_lawyers_text' => ['nullable', 'array'],
            'victim_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_lawyers_text_str' => ['nullable', 'string'],
            'victim_legal_rep_lawyers_text' => ['nullable', 'array'],
            'victim_legal_rep_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_lawyers_text_str' => ['nullable', 'string'],
            'civil_plaintiff_lawyers' => ['nullable', 'array'],
            'civil_plaintiff_lawyers.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_lawyers' => ['nullable', 'array'],
            'civil_defendant_lawyers.*' => ['nullable', 'string', 'max:255'],
            'matter_category_ids' => ['nullable', 'array'],
            'matter_category_ids.*' => ['nullable', 'integer', 'exists:matter_categories,id'],
            'victim_name' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep' => ['nullable', 'string', 'max:255'],
            'witnesses' => ['nullable', 'string'],
            'experts' => ['nullable', 'string'],
            'civil_plaintiff' => ['nullable', 'string', 'max:255'],
            'civil_defendant' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ], [
            'preventive_measure.*.in' => 'Таслан сэргийлэх арга хэмжээний сонголт буруу байна. Зөвхөн жагсаалтаас сонгоно уу.',
        ]);
        $data['title'] = $data['title'] ?? $data['hearing_state'] ?? 'Хэвийн';

        return $data;
    }

    private function assertProsecutorRole(int $userId): void
    {
        $ok = User::whereKey($userId)->role('prosecutor')->exists();
        if (! $ok) {
            throw ValidationException::withMessages([
                'prosecutor_id' => 'Сонгосон хэрэглэгч прокурор эрхтэй биш байна.',
            ]);
        }
    }
}
