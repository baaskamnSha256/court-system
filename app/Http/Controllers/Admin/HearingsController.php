<?php

namespace App\Http\Controllers\Admin;

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
        $isHeadOfDepartment = auth()->user()?->hasRole('head_of_department');

        // Огноо → цаг → танхимаар эрэмбэлнэ (эхлээд өдөр, дараа нь цаг, эцэст нь танхим)
        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
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

        // Төлөвөөр хурал зарлагдсан тоо (дүн мэдээ)
        $statsBase = Hearing::query();
        $countsByState = (clone $statsBase)
            ->selectRaw("COALESCE(hearing_state, 'Хэвийн') as state_key, count(*) as c")
            ->groupByRaw("COALESCE(hearing_state, 'Хэвийн')")
            ->get()
            ->pluck('c', 'state_key')
            ->toArray();
        $states = $this->allowedHearingStates();
        $hearingStateCounts = array_merge(array_fill_keys($states, 0), $countsByState);

        return view('hearings.index', [
            'hearings' => $hearings,
            'hearingStateCounts' => $hearingStateCounts,
            'states' => $states,
            'indexType' => $isHeadOfDepartment ? 'readonly' : 'admin',
            'headerTitle' => '',
            'listTitle' => 'Хурлын зарууд',
            'createUrl' => route('admin.hearings.create'),
            'createLabel' => 'Хурлын зар оруулах',
            'searchUrl' => route('admin.hearings.index'),
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
            $sourceHearing = Hearing::query()->find((int) $prefillSourceHearingId);
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
            'formAction' => route('admin.hearings.store'),
            'byDateUrl' => route('admin.hearings.byDate'),
            'editBaseUrl' => url('/admin/hearings'),
            'checkConflictUrl' => route('admin.hearings.checkConflict'),
            'backUrl' => route('admin.hearings.index'),
            'defendantSearchUrl' => route('admin.defendant-search'),
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

    /** Тухайн өдрийн хуралууд (JSON) — create хуудсын хажуу талд харуулах. Эхлэх цагаар эрэмбэлнэ, ижил цагт заалын нэрээр. */
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
                $prosecutorName = $h->prosecutors()->pluck('name')->implode(', ') ?: '—';

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

    /**
     * Admin form-оос ирсэн defendant_lawyers[], defendants гэх мэтийг controller-ийн талбаруудтай нийцүүлнэ.
     */
    private function normalizeAdminFormInputs(Request $request, array $data): array
    {
        if (empty($data['defendant_names']) && $request->filled('defendants')) {
            $data['defendant_names'] = array_values(array_filter(
                array_map('trim', preg_split('/[\n,]+/', $request->input('defendants')))
            ));
        }
        $rawDefendantRegistries = $request->input('defendant_registries', []);
        if (! is_array($rawDefendantRegistries)) {
            $rawDefendantRegistries = [];
        }
        $data['defendant_registries'] = collect($data['defendant_names'] ?? [])
            ->values()
            ->map(function ($_, $index) use ($rawDefendantRegistries) {
                return mb_strtoupper(trim((string) ($rawDefendantRegistries[$index] ?? '')), 'UTF-8');
            })
            ->all();
        $mapNamesWithRegistries = function (array $names, string $registryKey) use ($request): array {
            $rawRegistries = $request->input($registryKey, []);
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
        if (empty($data['defendant_lawyers_text']) && $request->has('defendant_lawyers')) {
            $data['defendant_lawyers_text'] = array_values(array_filter((array) $request->input('defendant_lawyers')));
        }
        if (empty($data['victim_lawyers_text']) && $request->has('victim_lawyers')) {
            $data['victim_lawyers_text'] = array_values(array_filter((array) $request->input('victim_lawyers')));
        }
        if (empty($data['victim_legal_rep_lawyers_text']) && $request->has('victim_legal_rep_lawyers')) {
            $data['victim_legal_rep_lawyers_text'] = array_values(array_filter((array) $request->input('victim_legal_rep_lawyers')));
        }
        $textToNames = function ($key, $fallback) use ($request) {
            $arr = $request->input($key, []);
            if (is_array($arr) && ! empty($arr)) {
                return $arr;
            }
            $s = $request->input($fallback);
            if (! is_string($s) || trim($s) === '') {
                return [];
            }

            return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $s))));
        };
        if (empty($data['victim_names'])) {
            $data['victim_names'] = $textToNames('victim_names', 'victim_name');
        }
        if (empty($data['victim_legal_rep_names'])) {
            $data['victim_legal_rep_names'] = $textToNames('victim_legal_rep_names', 'victim_legal_rep');
        }
        if (empty($data['witness_names'])) {
            $data['witness_names'] = $textToNames('witness_names', 'witnesses');
        }
        if (empty($data['expert_names'])) {
            $data['expert_names'] = $textToNames('expert_names', 'experts');
        }
        if (empty($data['civil_plaintiff_names'])) {
            $data['civil_plaintiff_names'] = $textToNames('civil_plaintiff_names', 'civil_plaintiff');
        }
        if (empty($data['civil_defendant_names'])) {
            $data['civil_defendant_names'] = $textToNames('civil_defendant_names', 'civil_defendant');
        }
        $data['victim_registries'] = $mapNamesWithRegistries((array) ($data['victim_names'] ?? []), 'victim_registries');
        $data['victim_legal_rep_registries'] = $mapNamesWithRegistries((array) ($data['victim_legal_rep_names'] ?? []), 'victim_legal_rep_registries');
        $data['witness_registries'] = $mapNamesWithRegistries((array) ($data['witness_names'] ?? []), 'witness_registries');
        $data['civil_plaintiff_registries'] = $mapNamesWithRegistries((array) ($data['civil_plaintiff_names'] ?? []), 'civil_plaintiff_registries');
        $data['civil_defendant_registries'] = $mapNamesWithRegistries((array) ($data['civil_defendant_names'] ?? []), 'civil_defendant_registries');
        if (empty($data['prosecutor_ids']) && ! empty($data['prosecutor_id'])) {
            $data['prosecutor_ids'] = [(int) $data['prosecutor_id']];
        }

        return $data;
    }

    /** Нэрсийн array-ийг шинэ мөрөөр нэгтгэсэн string болгоно (DB талбарт хадгалах) */
    private function namesArrayToText(array $names): string
    {
        return implode("\n", array_values(array_filter($names)));
    }

    public function edit(Hearing $hearing)
    {
        // ✅ Secretary зөвхөн өөрийн оруулсан хурлыг засна
        if (auth()->user()->hasRole('secretary') && (int) $hearing->created_by !== (int) auth()->id()) {
            abort(403, 'Та зөвхөн өөрийн оруулсан хурлын зарыг засварлах эрхтэй.');
        }

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
            'formAction' => route('admin.hearings.update', $hearing),
            'backUrl' => route('admin.hearings.index'),
            'checkConflictUrl' => route('admin.hearings.checkConflict'),
            'byDateUrl' => route('admin.hearings.byDate'),
            'editBaseUrl' => url('/admin/hearings'),
            'headerTitle' => 'Хурлын зар засварлах',
            'defendantSearchUrl' => route('admin.defendant-search'),
        ];

        return view('hearings.edit', array_merge(compact('hearing', 'judges', 'prosecutors', 'lawyers', 'matterCategories'), $vars));
    }

    /**
     * =========================
     * STORE
     * =========================
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'case_no' => ['required', 'string', 'max:255'],
            'hearing_state' => ['required', 'string', Rule::in($this->allowedHearingStates())],

            'hearing_type' => ['nullable', 'string', Rule::in(['Анхан шат', 'Урьдчилсан хэлэлцүүлэг'])],

            'hearing_date' => ['required', 'date'],
            'hour' => ['required', 'integer', 'min:8', 'max:18'],
            'minute' => ['required', 'integer', Rule::in($this->allowedMinutes())],

            'courtroom' => ['required', 'string', Rule::in($this->allowedCourtrooms())],

            'presiding_judge_id' => ['required', 'integer', 'exists:users,id'],
            'member_judge_1_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_2_id'],
            'member_judge_2_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_1_id'],

            // 2) Шүүгдэгч (multi, гараас) — array эсвэл defendants текстээр
            'defendant_names' => ['required', 'array', 'min:1'],
            'defendant_names.*' => ['required', 'string', 'max:255'],
            'defendant_registries' => ['nullable', 'array'],
            'defendant_registries.*' => ['nullable', 'string', 'max:20'],
            'defendants' => ['nullable', 'string'],

            'victim_names' => ['nullable', 'array'],
            'victim_names.*' => ['nullable', 'string', 'max:255'],
            'victim_registries' => ['nullable', 'array'],
            'victim_registries.*' => ['nullable', 'string', 'max:20'],
            'victim_name' => ['nullable', 'string'],
            'victim_legal_rep_names' => ['nullable', 'array'],
            'victim_legal_rep_names.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_registries' => ['nullable', 'array'],
            'victim_legal_rep_registries.*' => ['nullable', 'string', 'max:20'],
            'victim_legal_rep' => ['nullable', 'string'],
            'witness_names' => ['nullable', 'array'],
            'witness_names.*' => ['nullable', 'string', 'max:255'],
            'witness_registries' => ['nullable', 'array'],
            'witness_registries.*' => ['nullable', 'string', 'max:20'],
            'witnesses' => ['nullable', 'string'],
            'expert_names' => ['nullable', 'array'],
            'expert_names.*' => ['nullable', 'string', 'max:255'],
            'experts' => ['nullable', 'string'],
            'civil_plaintiff_names' => ['nullable', 'array'],
            'civil_plaintiff_names.*' => ['nullable', 'string', 'max:255'],
            'civil_plaintiff_registries' => ['nullable', 'array'],
            'civil_plaintiff_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_plaintiff' => ['nullable', 'string'],
            'civil_defendant_names' => ['nullable', 'array'],
            'civil_defendant_names.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_registries' => ['nullable', 'array'],
            'civil_defendant_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_defendant' => ['nullable', 'string'],

            // таслан сэргийлэх арга хэмжээ (олон сонголт)
            'preventive_measure' => ['required', 'array', 'min:1'],
            'preventive_measure.*' => ['required', 'string', Rule::in($this->allowedPreventiveMeasures())],

            // 3) прокурор (олон сонголт, системд бүртгэлтэй)
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['required', 'integer', 'exists:users,id'],
            'prosecutor_id' => ['nullable', 'integer', 'exists:users,id'],

            // 4-6 өмгөөлөгчид (гараас multi) — _text эсвэл хуучин defendant_lawyers гэж ирж болно
            'defendant_lawyers_text' => ['nullable', 'array'],
            'defendant_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'defendant_lawyers' => ['nullable', 'array'],
            'defendant_lawyers.*' => ['nullable', 'string', 'max:255'],

            'victim_lawyers_text' => ['nullable', 'array'],
            'victim_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_lawyers' => ['nullable', 'array'],
            'victim_lawyers.*' => ['nullable', 'string', 'max:255'],

            'victim_legal_rep_lawyers_text' => ['nullable', 'array'],
            'victim_legal_rep_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_lawyers' => ['nullable', 'array'],
            'victim_legal_rep_lawyers.*' => ['nullable', 'string', 'max:255'],

            'civil_plaintiff_lawyers' => ['nullable', 'array'],
            'civil_plaintiff_lawyers.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_lawyers' => ['nullable', 'array'],
            'civil_defendant_lawyers.*' => ['nullable', 'string', 'max:255'],

            'matter_category_ids' => ['nullable', 'array'],
            'matter_category_ids.*' => ['nullable', 'integer', 'exists:matter_categories,id'],

            'note' => ['nullable', 'string'],
        ], [
            'preventive_measure.*.in' => 'Таслан сэргийлэх арга хэмжээний сонголт буруу байна. Зөвхөн жагсаалтаас сонгоно уу.',
        ]);

        $data = $this->normalizeAdminFormInputs($request, $data);

        // Прокурор IDs цэгцлэх (олон сонголт); prosecutor_id-аас унших fallback
        $prosecutorIds = array_values(array_filter(array_map('intval', $data['prosecutor_ids'] ?? [])));
        if (empty($prosecutorIds) && ! empty($data['prosecutor_id'])) {
            $prosecutorIds = [(int) $data['prosecutor_id']];
        }
        foreach ($prosecutorIds as $pid) {
            if (! User::whereKey($pid)->role('prosecutor')->exists()) {
                throw ValidationException::withMessages([
                    'prosecutor_ids' => 'Сонгосон хэрэглэгч прокурор эрхтэй биш байна.',
                ]);
            }
        }

        // Шүүгчдийн id цэгцлэх
        $judgeIds = $this->normalizeJudgeIds($request);

        // ✅ Зөвхөн 1 эсвэл 3-г зөвшөөрнө (2 бол болохгүй)
        if (count($judgeIds) === 2) {
            throw ValidationException::withMessages([
                'member_judge_1_id' => '2 шүүгчтэй хурал зөвшөөрөгдөхгүй. 1 эсвэл 3 шүүгч сонгоно.',
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

        // Давхцал шалгах (танхим, шүүгч, өмгөөлөгч)
        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, null, $prosecutorIds);

        $hearing = DB::transaction(function () use ($request, $data, $start, $end, $duration, $prosecutorIds) {

            $hearing = Hearing::create([
                'created_by' => auth()->id(),

                'case_no' => $data['case_no'] ?? null,
                'title' => $data['hearing_state'] ?? 'Хэвийн',
                'hearing_type' => $data['hearing_type'] ?? null,
                'hearing_state' => $data['hearing_state'] ?? 'Хэвийн',

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
                'prosecutor_id' => $prosecutorIds[0] ?? null,
                'prosecutor_ids' => $prosecutorIds,

                'defendant_names' => array_values(array_filter($data['defendant_names'] ?? [])),
                'defendant_registries' => array_values($data['defendant_registries'] ?? []),
                'victim_registries' => array_values($data['victim_registries'] ?? []),
                'victim_legal_rep_registries' => array_values($data['victim_legal_rep_registries'] ?? []),
                'witness_registries' => array_values($data['witness_registries'] ?? []),
                'civil_plaintiff_registries' => array_values($data['civil_plaintiff_registries'] ?? []),
                'civil_defendant_registries' => array_values($data['civil_defendant_registries'] ?? []),

                'victim_name' => $this->namesArrayToText($data['victim_names'] ?? []),
                'victim_legal_rep' => $this->namesArrayToText($data['victim_legal_rep_names'] ?? []),
                'witnesses' => $this->namesArrayToText($data['witness_names'] ?? []),
                'experts' => $this->namesArrayToText($data['expert_names'] ?? []),
                'civil_plaintiff' => $this->namesArrayToText($data['civil_plaintiff_names'] ?? []),
                'civil_defendant' => $this->namesArrayToText($data['civil_defendant_names'] ?? []),

                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'civil_plaintiff_lawyers' => array_values(array_filter($data['civil_plaintiff_lawyers'] ?? [])),
                'civil_defendant_lawyers' => array_values(array_filter($data['civil_defendant_lawyers'] ?? [])),

                'matter_category_ids' => array_values(array_filter(array_map('intval', $data['matter_category_ids'] ?? []))),

                'note' => $data['note'] ?? null,
                'status' => 'scheduled',
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return $hearing;
        });

        $this->dispatchHearingNotification($hearing, 'created');

        return redirect()
            ->route('admin.hearings.index')
            ->with('success', 'Хурлын зар амжилттай бүртгэлээ.');
    }

    /**
     * =========================
     * UPDATE
     * =========================
     */
    public function update(Request $request, Hearing $hearing)
    {
        // ✅ Secretary зөвхөн өөрийн оруулсан хурлыг шинэчилнэ
        if (auth()->user()->hasRole('secretary') && (int) $hearing->created_by !== (int) auth()->id()) {
            abort(403, 'Та зөвхөн өөрийн оруулсан хурлын зарыг шинэчлэх эрхтэй.');
        }

        $data = $request->validate([
            'case_no' => ['required', 'string', 'max:255'],
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
            'defendants' => ['nullable', 'string'],

            'victim_names' => ['nullable', 'array'],
            'victim_names.*' => ['nullable', 'string', 'max:255'],
            'victim_registries' => ['nullable', 'array'],
            'victim_registries.*' => ['nullable', 'string', 'max:20'],
            'victim_name' => ['nullable', 'string'],
            'victim_legal_rep_names' => ['nullable', 'array'],
            'victim_legal_rep_names.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_registries' => ['nullable', 'array'],
            'victim_legal_rep_registries.*' => ['nullable', 'string', 'max:20'],
            'victim_legal_rep' => ['nullable', 'string'],
            'witness_names' => ['nullable', 'array'],
            'witness_names.*' => ['nullable', 'string', 'max:255'],
            'witness_registries' => ['nullable', 'array'],
            'witness_registries.*' => ['nullable', 'string', 'max:20'],
            'witnesses' => ['nullable', 'string'],
            'expert_names' => ['nullable', 'array'],
            'expert_names.*' => ['nullable', 'string', 'max:255'],
            'experts' => ['nullable', 'string'],
            'civil_plaintiff_names' => ['nullable', 'array'],
            'civil_plaintiff_names.*' => ['nullable', 'string', 'max:255'],
            'civil_plaintiff_registries' => ['nullable', 'array'],
            'civil_plaintiff_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_plaintiff' => ['nullable', 'string'],
            'civil_defendant_names' => ['nullable', 'array'],
            'civil_defendant_names.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_registries' => ['nullable', 'array'],
            'civil_defendant_registries.*' => ['nullable', 'string', 'max:20'],
            'civil_defendant' => ['nullable', 'string'],

            'preventive_measure' => ['required', 'array', 'min:1'],
            'preventive_measure.*' => ['required', 'string', Rule::in($this->allowedPreventiveMeasures())],
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['required', 'integer', 'exists:users,id'],
            'prosecutor_id' => ['nullable', 'integer', 'exists:users,id'],

            'defendant_lawyers_text' => ['nullable', 'array'],
            'defendant_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'defendant_lawyers' => ['nullable', 'array'],
            'defendant_lawyers.*' => ['nullable', 'string', 'max:255'],

            'victim_lawyers_text' => ['nullable', 'array'],
            'victim_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_lawyers' => ['nullable', 'array'],
            'victim_lawyers.*' => ['nullable', 'string', 'max:255'],

            'victim_legal_rep_lawyers_text' => ['nullable', 'array'],
            'victim_legal_rep_lawyers_text.*' => ['nullable', 'string', 'max:255'],
            'victim_legal_rep_lawyers' => ['nullable', 'array'],
            'victim_legal_rep_lawyers.*' => ['nullable', 'string', 'max:255'],

            'civil_plaintiff_lawyers' => ['nullable', 'array'],
            'civil_plaintiff_lawyers.*' => ['nullable', 'string', 'max:255'],
            'civil_defendant_lawyers' => ['nullable', 'array'],
            'civil_defendant_lawyers.*' => ['nullable', 'string', 'max:255'],

            'matter_category_ids' => ['nullable', 'array'],
            'matter_category_ids.*' => ['nullable', 'integer', 'exists:matter_categories,id'],

            'note' => ['nullable', 'string'],
        ], [
            'preventive_measure.*.in' => 'Таслан сэргийлэх арга хэмжээний сонголт буруу байна. Зөвхөн жагсаалтаас сонгоно уу.',
        ]);

        $data = $this->normalizeAdminFormInputs($request, $data);

        $prosecutorIds = array_values(array_filter(array_map('intval', $data['prosecutor_ids'] ?? [])));
        if (empty($prosecutorIds) && ! empty($data['prosecutor_id'])) {
            $prosecutorIds = [(int) $data['prosecutor_id']];
        }
        foreach ($prosecutorIds as $pid) {
            if (! User::whereKey($pid)->role('prosecutor')->exists()) {
                throw ValidationException::withMessages([
                    'prosecutor_ids' => 'Сонгосон хэрэглэгч прокурор эрхтэй биш байна.',
                ]);
            }
        }

        $judgeIds = $this->normalizeJudgeIds($request);

        if (count($judgeIds) === 2) {
            throw ValidationException::withMessages([
                'member_judge_1_id' => '2 шүүгчтэй хурал зөвшөөрөгдөхгүй. 1 эсвэл 3 шүүгч сонгоно.',
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

        // Давхцал шалгах (өөрийн record-оо 제외) — танхим, шүүгч, өмгөөлөгч
        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, $hearing->id, $prosecutorIds);

        $hearing = DB::transaction(function () use ($request, $data, $hearing, $start, $end, $duration, $prosecutorIds) {

            $hearing->update([
                'case_no' => $data['case_no'] ?? null,
                'title' => $data['hearing_state'] ?? $hearing->hearing_state ?? 'Хэвийн',
                'hearing_type' => $data['hearing_type'] ?? null,
                'hearing_state' => $data['hearing_state'] ?? 'Хэвийн',

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
                'prosecutor_id' => $prosecutorIds[0] ?? null,
                'prosecutor_ids' => $prosecutorIds,

                'defendant_names' => array_values(array_filter($data['defendant_names'] ?? [])),
                'defendant_registries' => array_values($data['defendant_registries'] ?? []),
                'victim_registries' => array_values($data['victim_registries'] ?? []),
                'victim_legal_rep_registries' => array_values($data['victim_legal_rep_registries'] ?? []),
                'witness_registries' => array_values($data['witness_registries'] ?? []),
                'civil_plaintiff_registries' => array_values($data['civil_plaintiff_registries'] ?? []),
                'civil_defendant_registries' => array_values($data['civil_defendant_registries'] ?? []),

                'victim_name' => $this->namesArrayToText($data['victim_names'] ?? []),
                'victim_legal_rep' => $this->namesArrayToText($data['victim_legal_rep_names'] ?? []),
                'witnesses' => $this->namesArrayToText($data['witness_names'] ?? []),
                'experts' => $this->namesArrayToText($data['expert_names'] ?? []),
                'civil_plaintiff' => $this->namesArrayToText($data['civil_plaintiff_names'] ?? []),
                'civil_defendant' => $this->namesArrayToText($data['civil_defendant_names'] ?? []),

                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'civil_plaintiff_lawyers' => array_values(array_filter($data['civil_plaintiff_lawyers'] ?? [])),
                'civil_defendant_lawyers' => array_values(array_filter($data['civil_defendant_lawyers'] ?? [])),

                'matter_category_ids' => array_values(array_filter(array_map('intval', $data['matter_category_ids'] ?? []))),

                'note' => $data['note'] ?? null,
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return $hearing->fresh();
        });

        $this->dispatchHearingNotification($hearing, 'updated');

        return redirect()
            ->route('admin.hearings.index')
            ->with('success', 'Хурлын зар амжилттай шинэчлэгдлээ.');
    }

    /**
     * Хурлын зарыг устгах
     */
    public function destroy(Hearing $hearing)
    {
        $hearing->judges()->detach();
        $hearing->delete();

        return redirect()
            ->route('admin.hearings.index')
            ->with('success', 'Хурлын зар устгагдлаа.');
    }

    /**
     * (optional) AJAX conflict check
     */
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

        $judgeIds = array_values(array_unique(array_filter([
            $data['presiding_judge_id'],
            $data['member_judge_1_id'] ?? null,
            $data['member_judge_2_id'] ?? null,
        ])));

        if (count($judgeIds) === 2) {
            return response()->json([
                'ok' => false,
                'field' => 'member_judge_1_id',
                'message' => '2 шүүгчтэй хурал зөвшөөрөгдөхгүй. 1 эсвэл 3 шүүгч сонгоно.',
            ]);
        }

        $duration = count($judgeIds) >= 3 ? 60 : 30;

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
}
