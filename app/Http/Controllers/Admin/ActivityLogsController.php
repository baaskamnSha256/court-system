<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogsController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->with(['user:id,name,email'])
            ->latest('id');

        if ($request->filled('user')) {
            $u = '%'.$request->string('user')->trim().'%';
            $query->whereHas('user', function ($userQuery) use ($u) {
                $userQuery->where('name', 'like', $u)
                    ->orWhere('email', 'like', $u);
            });
        }

        if ($request->filled('case_no')) {
            $caseNo = $request->string('case_no')->trim();
            $query->where('properties->case_no', 'like', '%'.$caseNo.'%');
        }

        if ($request->filled('category')) {
            $category = $request->string('category')->toString();
            if ($category === 'auth') {
                $query->where('action', 'like', 'auth.%');
            } elseif ($category === 'http') {
                $query->where('action', 'like', 'http.%');
            } elseif ($category === 'file') {
                $query->where('action', 'file.download');
            } elseif ($category === 'hearing') {
                $query->where('action', 'like', 'hearing.%');
            }
        }

        return view('admin.activity-logs.index', [
            'logs' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'perPage' => self::PER_PAGE,
        ]);
    }
}
