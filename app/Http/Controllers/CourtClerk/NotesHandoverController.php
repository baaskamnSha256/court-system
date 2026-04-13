<?php

namespace App\Http\Controllers\CourtClerk;

use App\Http\Controllers\Concerns\NormalizesNotesDefendantSentences;
use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotesHandoverController extends Controller
{
    use NormalizesNotesDefendantSentences;

    public function index(Request $request)
    {
        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->where('clerk_id', auth()->id());

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
        if ($request->filled('notes_handover_issued')) {
            $issued = filter_var($request->input('notes_handover_issued'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($issued !== null) {
                $query->where('notes_handover_issued', $issued);
            }
        }

        $hearings = $query
            ->orderBy('hearing_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->orderBy('courtroom')
            ->paginate(20)
            ->withQueryString();

        $matterCategories = MatterCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.notes_handover.index', [
            'headerTitle' => 'Тэмдэглэл хүлээлцэх',
            'hearings' => $hearings,
            'clerks' => collect(),
            'allMatterCategories' => $matterCategories,
            'notesRoutePrefix' => 'court_clerk',
        ]);
    }

    public function update(Request $request, Hearing $hearing)
    {
        if ((int) $hearing->clerk_id !== (int) auth()->id()) {
            abort(403, 'Та зөвхөн өөрт оноогдсон хурлын тэмдэглэлийг шинэчилж чадна.');
        }

        $data = $request->validate([
            'notes_handover_text' => ['nullable', 'string'],
            'notes_decided_matter_ids' => ['nullable', 'array'],
            'notes_decided_matter_ids.*' => ['integer', 'exists:matter_categories,id'],
            'notes_fine_units' => ['nullable', 'string', 'max:100'],
            'notes_damage_amount' => ['nullable', 'string', 'max:100'],
            'notes_decision_status' => ['required', 'string', 'max:100'],
            'notes_defendant_sentences' => ['nullable', 'array'],
            'notes_defendant_sentences.*.defendant_name' => ['nullable', 'string', 'max:255'],
            'notes_defendant_sentences.*.defendant_registry' => ['nullable', 'string', 'max:20'],
            'notes_defendant_sentences.*.decided_matter_ids' => ['nullable', 'array'],
            'notes_defendant_sentences.*.decided_matter_ids.*' => ['integer', 'exists:matter_categories,id'],
            'notes_defendant_sentences.*.punishments' => ['nullable', 'array'],
            'notes_defendant_sentences.*.outcome_track' => ['nullable', 'string', Rule::in(['sentence', 'no_sentence', 'termination'])],
            'notes_defendant_sentences.*.termination_kind' => ['nullable', 'string', Rule::in(['dismiss', 'acquit'])],
            'notes_defendant_sentences.*.termination_note' => ['nullable', 'string', 'max:5000'],
            'notes_defendant_sentences.*.special_outcome' => ['nullable', 'string', Rule::in(static::specialOutcomeOptionValues())],
            'notes_defendant_sentences.*.allocations' => ['nullable', 'array'],
            'notes_defendant_sentences.*.allocations.*.matter_category_id' => ['nullable', 'integer', 'exists:matter_categories,id'],
            'notes_defendant_sentences.*.allocations.*.punishments' => ['nullable', 'array'],
        ], [
            'notes_decision_status.required' => 'Шүүх хуралдааны шийдвэрийг заавал сонгоно уу.',
        ]);

        $hearing->notes_handover_text = $data['notes_handover_text'] ?? $hearing->notes_handover_text;

        $normalizedDefendantSentences = $this->normalizeDefendantSentences($data['notes_defendant_sentences'] ?? []);
        $normalizedDefendantSentences = $this->restoreMissingDecidedMatterIdsFromExisting(
            $normalizedDefendantSentences,
            is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : []
        );
        $mustValidateSentences = false;
        if (($data['notes_decision_status'] ?? '') === 'Шийдвэрлэсэн') {
            if ($mustValidateSentences && empty($normalizedDefendantSentences)) {
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
                $this->assertNotesDefendantSentenceValidWhenIssued($index, $sentence, $mustValidateSentences);
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
            $originalNames = $hearing->matterCategories()->sortBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = ! empty($originalNames) ? implode(', ', $originalNames) : null;
            $hearing->notes_fine_units = (string) $this->parseGroupedNumber($data['notes_fine_units'] ?? '');
            $hearing->notes_damage_amount = (string) $this->parseGroupedNumber($data['notes_damage_amount'] ?? '');
        }
        $hearing->notes_defendant_sentences = $normalizedDefendantSentences;
        $hearing->notes_decision_status = $data['notes_decision_status'] ?? $hearing->notes_decision_status;

        $hearing->save();

        return back()->with('success', 'Тэмдэглэл амжилттай хадгаллаа.');
    }
}
