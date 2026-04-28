<?php

use App\Jobs\SendEmongoliaNotificationJob;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Notifications\HearingNotificationService;
use App\Services\Notifications\NotificationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('fetches one-time token from notification.mn', function () {
    config()->set('services.notification.token_url', 'https://notification.mn/api/v1/external/token');
    config()->set('services.notification.username', 'user');
    config()->set('services.notification.password', 'pass');

    Http::fake([
        'notification.mn/*' => Http::response(['token' => 'abc.def.ghi'], 200),
    ]);

    $token = app(NotificationTokenService::class)->getOneTimeToken();

    expect($token)->toBe('abc.def.ghi');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://notification.mn/api/v1/external/token'
            && $request['username'] === 'user'
            && $request['password'] === 'pass'
            && $request->hasHeader('Content-Type', 'application/json');
    });
});

it('throws when token response is empty', function () {
    config()->set('services.notification.token_url', 'https://notification.mn/api/v1/external/token');
    config()->set('services.notification.username', 'user');
    config()->set('services.notification.password', 'pass');

    Http::fake([
        'notification.mn/*' => Http::response([], 200),
    ]);

    app(NotificationTokenService::class)->getOneTimeToken();
})->throws(RuntimeException::class);

it('dispatches notification job for each recipient with regnum', function () {
    Queue::fake();

    config()->set('services.notification.enabled', true);
    config()->set('services.notification.access_token', 'fixed-access-token');
    config()->set('services.notification.notify_url', 'https://notification.mn/api/v1/notification');

    Role::firstOrCreate(['name' => 'judge', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'prosecutor', 'guard_name' => 'web']);

    $judge = User::factory()->create(['name' => 'Judge', 'register_number' => 'AB12345678']);
    $judge->assignRole('judge');

    $prosecutor = User::factory()->create(['name' => 'Prosecutor', 'register_number' => 'CD12345678']);
    $prosecutor->assignRole('prosecutor');

    $hearing = Hearing::create([
        'created_by' => $judge->id,
        'case_no' => '2026/100',
        'title' => 'Тест',
        'hearing_state' => 'Хэвийн',
        'hearing_date' => now()->toDateString(),
        'hour' => 9,
        'minute' => 0,
        'start_at' => now()->setTime(9, 0),
        'end_at' => now()->setTime(9, 30),
        'duration_minutes' => 30,
        'courtroom' => '1',
        'preventive_measure' => 'Цагдан хорих',
        'prosecutor_id' => $prosecutor->id,
        'prosecutor_ids' => [$prosecutor->id],
        'defendant_names' => ['Defendant'],
        'status' => 'scheduled',
    ]);
    $hearing->judges()->attach($judge->id, ['position' => 1]);

    app(HearingNotificationService::class)->send($hearing, 'created');

    Queue::assertPushed(SendEmongoliaNotificationJob::class, 2);
    Queue::assertPushed(
        SendEmongoliaNotificationJob::class,
        fn (SendEmongoliaNotificationJob $job) => $job->regnum === 'AB12345678'
            && str_contains(json_encode($job->body), '2026/100')
    );
});

it('skips dispatching when notifications are disabled', function () {
    Queue::fake();

    config()->set('services.notification.enabled', false);

    Role::firstOrCreate(['name' => 'judge', 'guard_name' => 'web']);
    $judge = User::factory()->create(['register_number' => 'AB12345678']);
    $judge->assignRole('judge');

    $hearing = Hearing::create([
        'created_by' => $judge->id,
        'case_no' => '2026/101',
        'title' => 'Тест',
        'hearing_state' => 'Хэвийн',
        'hearing_date' => now()->toDateString(),
        'hour' => 9,
        'minute' => 0,
        'start_at' => now()->setTime(9, 0),
        'end_at' => now()->setTime(9, 30),
        'duration_minutes' => 30,
        'courtroom' => '1',
        'preventive_measure' => 'Цагдан хорих',
        'defendant_names' => ['Defendant'],
        'status' => 'scheduled',
    ]);
    $hearing->judges()->attach($judge->id, ['position' => 1]);

    app(HearingNotificationService::class)->send($hearing, 'created');

    Queue::assertNothingPushed();
});

it('sends notification with correct headers and body when job handled', function () {
    config()->set('services.notification.enabled', true);
    config()->set('services.notification.access_token', 'fixed-access-token');
    config()->set('services.notification.notify_url', 'https://notification.mn/api/v1/notification');
    config()->set('services.notification.token_url', 'https://notification.mn/api/v1/external/token');
    config()->set('services.notification.username', 'user');
    config()->set('services.notification.password', 'pass');

    Http::fake([
        'notification.mn/api/v1/external/token' => Http::response(['token' => 'one-time-xyz'], 200),
        'notification.mn/api/v1/notification' => Http::response([
            'status' => 200,
            'Message' => 'Амжилттай',
            'RequestId' => 'req-uuid-123',
        ], 200),
    ]);

    $job = new SendEmongoliaNotificationJob(
        title: 'Шүүх хурал товлогдлоо',
        body: ['Mail' => 'M', 'Messenger' => 'M', 'Notification' => 'N'],
        regnum: 'AB12345678',
        civilId: 'AB12345678',
        context: ['hearing_id' => 1]
    );

    $job->handle(app(NotificationTokenService::class));

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/notification')) {
            return false;
        }

        $body = $request->data();

        return $request->hasHeader('Accesstoken', 'fixed-access-token')
            && $request->hasHeader('Authorization', 'Bearer one-time-xyz')
            && ($body['title'] ?? null) === 'Шүүх хурал товлогдлоо'
            && ($body['regnum'] ?? null) === 'AB12345678'
            && ($body['civilId'] ?? null) === 'AB12345678'
            && is_array($body['body'] ?? null)
            && isset($body['body']['Mail'], $body['body']['Messenger'], $body['body']['Notification']);
    });
});

it('treats api status non-200 as failure even with HTTP 200', function () {
    config()->set('services.notification.enabled', true);
    config()->set('services.notification.access_token', 'fixed-access-token');
    config()->set('services.notification.notify_url', 'https://notification.mn/api/v1/notification');
    config()->set('services.notification.token_url', 'https://notification.mn/api/v1/external/token');
    config()->set('services.notification.username', 'user');
    config()->set('services.notification.password', 'pass');

    Http::fake([
        'notification.mn/api/v1/external/token' => Http::response(['token' => 'tkn'], 200),
        'notification.mn/api/v1/notification' => Http::response([
            'status' => 400,
            'Message' => 'Регистр буруу',
            'RequestId' => 'req-uuid-err',
        ], 200),
    ]);

    \Illuminate\Support\Facades\Log::shouldReceive('warning')->atLeast()->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->never();
    \Illuminate\Support\Facades\Log::shouldReceive('error')->andReturnNull();

    $job = new SendEmongoliaNotificationJob(
        title: 'T',
        body: 'B',
        regnum: 'BAD',
        civilId: 'BAD'
    );

    $job->handle(app(NotificationTokenService::class));
});
