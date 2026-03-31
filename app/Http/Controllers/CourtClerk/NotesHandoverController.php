<?php

namespace App\Http\Controllers\CourtClerk;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use Illuminate\Http\Request;

class NotesHandoverController extends Controller
{
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
        ], [
            'notes_decision_status.required' => 'Шүүх хуралдааны шийдвэрийг заавал сонгоно уу.',
        ]);

        $hearing->notes_handover_text = $data['notes_handover_text'] ?? $hearing->notes_handover_text;

        if (! empty($data['notes_decided_matter_ids']) && is_array($data['notes_decided_matter_ids'])) {
            $ids = array_map('intval', $data['notes_decided_matter_ids']);
            $names = MatterCategory::whereIn('id', $ids)->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = $names ? implode(', ', $names) : null;
        } else {
            $originalNames = $hearing->matterCategories()->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = ! empty($originalNames) ? implode(', ', $originalNames) : null;
        }

        $hearing->notes_fine_units = $data['notes_fine_units'] ?? $hearing->notes_fine_units;
        $hearing->notes_damage_amount = $data['notes_damage_amount'] ?? $hearing->notes_damage_amount;
        $hearing->notes_decision_status = $data['notes_decision_status'] ?? $hearing->notes_decision_status;

        $hearing->save();

        return back()->with('success', 'Тэмдэглэл амжилттай хадгаллаа.');
    }
}
