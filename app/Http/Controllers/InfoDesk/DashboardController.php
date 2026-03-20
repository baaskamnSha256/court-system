<?php

namespace App\Http\Controllers\InfoDesk;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const EXPORT_LIMIT = 1000;

    public function index(Request $request)
    {
        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        if ($request->filled('date_from')) {
            $query->whereDate('hearing_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('hearing_date', '<=', $request->input('date_to'));
        }

        $hearings = $query->paginate(20)->withQueryString();

        return view('info_desk.dashboard', [
            'hearings' => $hearings,
            'searchUrl' => route('info_desk.dashboard'),
            'downloadUrl' => route('info_desk.hearings.print.download', $request->query()),
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }
}
