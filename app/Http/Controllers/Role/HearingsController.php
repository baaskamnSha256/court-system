<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HearingsController extends Controller
{
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

        return view('hearings.index', [
            'headerTitle' => 'Хурлын зар (Шүүгч)',
            'listTitle' => 'Миний оролцох хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('judge.hearings.index'),
            'createUrl' => route('judge.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
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

        return view('hearings.index', [
            'headerTitle' => 'Хурлын зар (Прокурор)',
            'listTitle' => 'Миний оролцох хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('prosecutor.hearings.index'),
            'createUrl' => route('prosecutor.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
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

        return view('hearings.index', [
            'headerTitle' => 'Хурлын зар (Өмгөөлөгч)',
            'listTitle' => 'Миний оролцох хурлын зарууд',
            'hearings' => $hearings,
            'indexType' => 'readonly',
            'searchUrl' => route('lawyer.hearings.index'),
            'createUrl' => route('lawyer.hearings.index'),
            'createLabel' => 'Шинэ зар оруулах эрхгүй',
            'courtrooms' => $this->allowedCourtrooms(),
        ]);
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        if ($request->filled('q')) {
            $search = '%' . $request->input('q') . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('case_no', 'like', $search)
                    ->orWhere('courtroom', 'like', $search)
                    ->orWhere('defendants', 'like', $search);
            });
        }

        if ($request->filled('hearing_date')) {
            $query->whereDate('hearing_date', $request->input('hearing_date'));
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
}
