<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(string $action, string $description, ?Model $subject = null, array $properties = [], ?int $actingUserId = null): ActivityLog
    {
        $request = request();

        $merged = $properties;
        if ($request instanceof Request) {
            $routeName = $request->route()?->getName();
            if ($routeName !== null && $routeName !== '' && ! array_key_exists('route', $merged)) {
                $merged['route'] = $routeName;
            }
        }

        $userId = $actingUserId ?? Auth::id();

        return ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $merged === [] ? null : $merged,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
        ]);
    }
}
