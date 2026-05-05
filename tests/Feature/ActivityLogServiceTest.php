<?php

use App\Models\ActivityLog;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Auth;

it('records activity with authenticated user and subject', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $hearing = Hearing::query()->create([
        'title' => 'Тест хурал',
        'case_no' => 'SRV-001',
        'status' => 'scheduled',
        'courtroom' => 'A',
        'start_at' => now()->setTime(10, 0),
        'end_at' => now()->setTime(10, 10),
    ]);

    $log = app(ActivityLogService::class)->record(
        'hearing.updated',
        'Хурлын зар шинэчиллээ',
        $hearing,
        ['case_no' => $hearing->case_no, 'hearing_id' => $hearing->id]
    );

    expect($log->exists)->toBeTrue()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->action)->toBe('hearing.updated')
        ->and($log->subject_type)->toBe($hearing->getMorphClass())
        ->and((int) $log->subject_id)->toBe((int) $hearing->id)
        ->and($log->properties)->toMatchArray([
            'case_no' => 'SRV-001',
            'hearing_id' => $hearing->id,
        ]);

    expect(ActivityLog::query()->count())->toBe(1);
});
