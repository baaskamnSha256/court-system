<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\User;
use App\Models\MatterCategory;
use Illuminate\Http\Request;

class NotesHandoverController extends Controller
{
    public function index(Request $request)
    {
        $query = Hearing::query()->with(['judges', 'prosecutor']);

        if ($request->filled('hearing_date')) {
            $query->whereDate('hearing_date', $request->date('hearing_date'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($qq) use ($q) {
                $qq->where('case_no', 'like', "%{$q}%")
                    ->orWhere('courtroom', 'like', "%{$q}%")
                    ->orWhere('defendants', 'like', "%{$q}%");
            });
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
            'notesRoutePrefix' => 'admin',
        ]);
    }

    public function update(Request $request, Hearing $hearing)
    {
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
            'notes_handover_issued' => ['nullable', 'boolean'],
            'clerk_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'notes_decision_status.required' => 'Шүүх хуралдааны шийдвэрийг заавал сонгоно уу.',
            'clerk_id.required' => 'Шүүх хуралдааны нарийн бичгийг заавал сонгоно уу.',
        ]);

        // Admin can edit all, secretary can edit only hearings they created?
        // For now, trust role middleware + UI.

        if (auth()->user()->hasAnyRole(['admin', 'secretary'])) {
            $hearing->clerk_id = $data['clerk_id'] ?? null;
            if (($data['clerk_id'] ?? null) !== $oldClerkId) {
                $hearing->notes_clerk_selected_at = !empty($data['clerk_id']) ? now() : null;
            }
            $issued = (bool)($data['notes_handover_issued'] ?? false);
            $hearing->notes_handover_issued = $issued;
            if ($issued && !$oldIssued) {
                $hearing->notes_handover_issued_at = now();
            } elseif (!$issued) {
                $hearing->notes_handover_issued_at = null;
            }
        }

        $hearing->notes_handover_text = $data['notes_handover_text'] ?? $hearing->notes_handover_text;

        // Шийдвэрлэсэн зүйл анги — сонгосон зүйл ангиудын нэрсийг нэг мөрөнд хадгална
        if (!empty($data['notes_decided_matter_ids']) && is_array($data['notes_decided_matter_ids'])) {
            $ids = array_map('intval', $data['notes_decided_matter_ids']);
            $names = MatterCategory::whereIn('id', $ids)->orderBy('sort_order')->pluck('name')->all();
            $hearing->notes_decided_matter = $names ? implode(', ', $names) : null;
        } else {
            $hearing->notes_decided_matter = null;
        }
        $hearing->notes_fine_units = $data['notes_fine_units'] ?? $hearing->notes_fine_units;
        $hearing->notes_damage_amount = $data['notes_damage_amount'] ?? $hearing->notes_damage_amount;
        $hearing->notes_decision_status = $data['notes_decision_status'] ?? $hearing->notes_decision_status;

        // Бүртгэсэн цаг: "Тэмдэглэл гаргасан" гэж тэмдэглэсэн яг тэр мөчид
        if ($hearing->notes_handover_issued && !$oldIssued) {
            $hearing->notes_handover_saved_at = now();
        }

        $hearing->save();

        return back()->with('success', 'Тэмдэглэл амжилттай хадгаллаа.');
    }
}
