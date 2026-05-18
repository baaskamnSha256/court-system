<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Concerns\ScopesHearingsFromToday;
use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HearingsController extends Controller
{
    use ScopesHearingsFromToday;

    public function judgeIndex(Request $request): View
    {
        $userId = (int) auth()->id();

        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->whereHas('judges', fn (Builder $query) => $query->where('users.id', $userId))
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        $this->applyFilters($request, $query);
        $hearings = $query->paginate(20)->withQueryString();

        $showDecisionColumns = $request->filled('notes_decision_status');

        return view('hearings.index', [
            'headerTitle' => '',
            'listTitle' => 'Миний хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('judge.hearings.index'),
            'createUrl' => route('judge.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
            'showDecisionColumns' => $showDecisionColumns,
            'matterNamesById' => $showDecisionColumns ? $this->matterNamesById() : collect(),
        ]);
    }

    public function prosecutorIndex(Request $request): View
    {
        $userId = (int) auth()->id();

        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->where(function (Builder $query) use ($userId) {
                $query->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            })
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        $this->applyFilters($request, $query);
        $hearings = $query->paginate(20)->withQueryString();

        $showDecisionColumns = $request->filled('notes_decision_status');

        return view('hearings.index', [
            'headerTitle' => '',
            'listTitle' => 'Миний хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('prosecutor.hearings.index'),
            'createUrl' => route('prosecutor.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
            'showDecisionColumns' => $showDecisionColumns,
            'matterNamesById' => $showDecisionColumns ? $this->matterNamesById() : collect(),
        ]);
    }

    public function lawyerIndex(Request $request): View
    {
        $lawyerName = (string) auth()->user()->name;

        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->where(function (Builder $query) use ($lawyerName) {
                $query->whereJsonContains('defendant_lawyers_text', $lawyerName)
                    ->orWhereJsonContains('victim_lawyers_text', $lawyerName)
                    ->orWhereJsonContains('victim_legal_rep_lawyers_text', $lawyerName)
                    ->orWhereJsonContains('civil_plaintiff_lawyers', $lawyerName)
                    ->orWhereJsonContains('civil_defendant_lawyers', $lawyerName);
            })
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        $this->applyFilters($request, $query);
        $hearings = $query->paginate(20)->withQueryString();

        $showDecisionColumns = $request->filled('notes_decision_status');

        return view('hearings.index', [
            'headerTitle' => '',
            'listTitle' => 'Миний хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('lawyer.hearings.index'),
            'createUrl' => route('lawyer.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
            'showDecisionColumns' => $showDecisionColumns,
            'matterNamesById' => $showDecisionColumns ? $this->matterNamesById() : collect(),
        ]);
    }

    public function courtClerkIndex(Request $request): View
    {
        $userId = (int) auth()->id();

        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->where('clerk_id', $userId)
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        $this->applyFilters($request, $query);
        $hearings = $query->paginate(20)->withQueryString();

        return view('hearings.index', [
            'headerTitle' => '',
            'listTitle' => 'Хурлын зар',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('court_clerk.hearings.index'),
            'createUrl' => route('court_clerk.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
        ]);
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        $this->applyHearingsVisibleFromToday($query);

        if ($request->filled('q')) {
            $search = '%'.$request->input('q').'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('case_no', 'like', $search)
                    ->orWhere('courtroom', 'like', $search)
                    ->orWhere('defendants', 'like', $search);
            });
        }

        if ($request->filled('hearing_date')) {
            $query->whereDate('hearing_date', $request->input('hearing_date'));
        }

        if ($request->filled('hearing_date_from') && $request->filled('hearing_date_to')) {
            $from = $request->input('hearing_date_from');
            $to = $request->input('hearing_date_to');
            $query->where(function (Builder $outer) use ($from, $to) {
                $outer->where(function (Builder $q) use ($from, $to) {
                    $q->whereNotNull('hearing_date')
                        ->whereBetween('hearing_date', [$from, $to]);
                })->orWhere(function (Builder $q) use ($from, $to) {
                    $q->whereBetween('start_at', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay(),
                    ]);
                });
            });
        }

        if ($request->filled('courtroom')) {
            $query->where('courtroom', $request->input('courtroom'));
        }

        if ($request->filled('notes_decision_status')) {
            $status = (string) $request->input('notes_decision_status');
            if ($status === '__pending__') {
                $query->where(function (Builder $builder) {
                    $builder->whereNull('notes_decision_status')
                        ->orWhere('notes_decision_status', '');
                });
            } else {
                $query->where('notes_decision_status', $status);
            }
        }
    }

    private function allowedCourtrooms(): array
    {
        return ['A', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж'];
    }

    /** @return Collection<int, string> */
    private function matterNamesById(): Collection
    {
        return MatterCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
