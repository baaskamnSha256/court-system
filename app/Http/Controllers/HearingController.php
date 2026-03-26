<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesHearingLogic;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HearingController extends Controller
{
    use ManagesHearingLogic;

    public function index()
    {
        $hearings = Hearing::with('judges')
            ->orderBy('start_at', 'desc')
            ->paginate(10);

        return view('court_clerk.hearings.index', compact('hearings'));
    }

    public function create()
    {
        $judges = User::role('judge')->where('is_active', true)->orderBy('name')->get();
        $prosecutors = User::role('prosecutor')->where('is_active', true)->orderBy('name')->get();
        $lawyers = User::role('lawyer')->where('is_active', true)->orderBy('name')->get();
        $courtrooms = $this->allowedCourtrooms();
        $minutes = $this->allowedMinutes();
        $preventiveMeasures = $this->allowedPreventiveMeasures();

        return view('court_clerk.hearings.create', compact(
            'judges', 'prosecutors', 'lawyers', 'courtrooms', 'minutes', 'preventiveMeasures'
        ));
    }

    public function edit(Hearing $hearing)
    {
        $hearing->load('judges');
        $selectedJudgeIds = $hearing->judges->pluck('id')->map(fn ($id) => (int) $id)->values();
        if ($hearing->judge_id) {
            $selectedJudgeIds->push((int) $hearing->judge_id);
        }
        $selectedJudgeIds = $selectedJudgeIds->unique()->values();

        $judges = $this->judgesForHearingForm($selectedJudgeIds);
        $prosecutors = User::role('prosecutor')->where('is_active', true)->orderBy('name')->get();
        $lawyers = User::role('lawyer')->where('is_active', true)->orderBy('name')->get();
        $courtrooms = $this->allowedCourtrooms();
        $minutes = $this->allowedMinutes();
        $preventiveMeasures = $this->allowedPreventiveMeasures();

        return view('court_clerk.hearings.edit', compact(
            'hearing', 'judges', 'prosecutors', 'lawyers', 'courtrooms', 'minutes', 'preventiveMeasures'
        ));
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
            $data['victim_legal_rep_lawyers_text'] ?? []
        ))));

        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, null, $data['prosecutor_id'] ? [$data['prosecutor_id']] : []);

        return DB::transaction(function () use ($request, $data, $start, $end, $duration) {
            $hearing = Hearing::create([
                'created_by' => auth()->id(),
                'case_no' => $data['case_no'] ?? null,
                'title' => $data['title'],
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
                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'note' => $data['note'] ?? null,
                'status' => 'scheduled',
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return redirect()->route('court_clerk.hearings.index')->with('success', 'Хурлын зар бүртгэлээ.');
        });
    }

    public function update(Request $request, Hearing $hearing)
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
            $data['victim_legal_rep_lawyers_text'] ?? []
        ))));

        $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, $lawyerNames, $hearing->id, $data['prosecutor_id'] ? [$data['prosecutor_id']] : []);

        return DB::transaction(function () use ($request, $data, $hearing, $start, $end, $duration) {
            $hearing->update([
                'case_no' => $data['case_no'] ?? null,
                'title' => $data['title'],
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
                'defendant_lawyers_text' => array_values(array_filter($data['defendant_lawyers_text'] ?? [])),
                'victim_lawyers_text' => array_values(array_filter($data['victim_lawyers_text'] ?? [])),
                'victim_legal_rep_lawyers_text' => array_values(array_filter($data['victim_legal_rep_lawyers_text'] ?? [])),
                'note' => $data['note'] ?? null,
            ]);

            $this->syncJudgesPivot($hearing, $request);

            return redirect()->route('court_clerk.hearings.index')->with('success', 'Хурлын зар шинэчлэгдлээ.');
        });
    }

    public function destroy(Hearing $hearing)
    {
        $hearing->delete();

        return back()->with('success', 'Устгалаа.');
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

        try {
            $prosecutorIds = array_values(array_filter(array_unique(array_map('intval', $data['prosecutor_ids'] ?? []))));
            $this->assertNoConflict($start, $end, $data['courtroom'], $judgeIds, [], $data['ignore_id'] ?? null, $prosecutorIds);

            return response()->json(['ok' => true, 'field' => null]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $field = collect(array_keys($errors))->first();
            $msg = collect($errors)->flatten()->first() ?? 'Давхцал илэрлээ.';

            return response()->json(['ok' => false, 'field' => $field, 'message' => $msg]);
        }
    }

    private function validateHearing(Request $request): array
    {
        return $request->validate([
            'case_no' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'hearing_type' => ['nullable', 'string', Rule::in(['Анхан шат', 'Урьдчилсан хэлэлцүүлэг'])],
            'hearing_date' => ['required', 'date'],
            'hour' => ['required', 'integer', 'min:8', 'max:18'],
            'minute' => ['required', 'integer', Rule::in($this->allowedMinutes())],
            'courtroom' => ['required', 'string', Rule::in($this->allowedCourtrooms())],
            'presiding_judge_id' => ['required', 'integer', 'exists:users,id'],
            'member_judge_1_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_2_id'],
            'member_judge_2_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:member_judge_1_id'],
            'defendant_names' => ['nullable', 'array'],
            'defendant_names.*' => ['nullable', 'string', 'max:255'],
            'defendant_names_text' => ['nullable', 'string'],
            'preventive_measure' => ['nullable', 'array'],
            'preventive_measure.*' => ['nullable', 'string', Rule::in($this->allowedPreventiveMeasures())],
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
            'note' => ['nullable', 'string'],
        ]);
    }

    private function normalizeFormData(array $data): array
    {
        if (empty($data['defendant_names']) && ! empty($data['defendant_names_text'] ?? '')) {
            $data['defendant_names'] = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $data['defendant_names_text']))));
        }
        if (empty($data['defendant_lawyers_text']) && ! empty($data['defendant_lawyers_text_str'] ?? '')) {
            $data['defendant_lawyers_text'] = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $data['defendant_lawyers_text_str']))));
        }
        if (empty($data['victim_lawyers_text']) && ! empty($data['victim_lawyers_text_str'] ?? '')) {
            $data['victim_lawyers_text'] = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $data['victim_lawyers_text_str']))));
        }
        if (empty($data['victim_legal_rep_lawyers_text']) && ! empty($data['victim_legal_rep_lawyers_text_str'] ?? '')) {
            $data['victim_legal_rep_lawyers_text'] = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $data['victim_legal_rep_lawyers_text_str']))));
        }

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
