<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationLog::query()
            ->with('hearing:id,case_no')
            ->latest('id');

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->string('delivery_status'));
        }

        if ($request->filled('case_no')) {
            $caseNo = $request->string('case_no')->trim();
            $query->whereHas('hearing', fn ($hearingQuery) => $hearingQuery->where('case_no', 'like', '%'.$caseNo.'%'));
        }

        if ($request->filled('regnum')) {
            $query->where('regnum', 'like', '%'.$request->string('regnum')->trim().'%');
        }

        if ($request->filled('request_id')) {
            $query->where('request_id', 'like', '%'.$request->string('request_id')->trim().'%');
        }

        return view('admin.notifications.logs.index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'statuses' => NotificationLog::query()
                ->select('delivery_status')
                ->whereNotNull('delivery_status')
                ->distinct()
                ->orderBy('delivery_status')
                ->pluck('delivery_status')
                ->all(),
        ]);
    }
}
