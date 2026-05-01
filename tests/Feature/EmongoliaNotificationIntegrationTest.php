<?php

use App\Jobs\SendEmongoliaNotificationJob;
use App\Models\Hearing;
use App\Models\NotificationLog;
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
    config()->set('services.notification.dispatch_sync', false);
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
    );
});

it('dispatches jobs for non-user participants using hearing registries', function () {
    Queue::fake();

    config()->set('services.notification.enabled', true);
    config()->set('services.notification.dispatch_sync', false);
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
        'case_no' => '2026/777',
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
        'defendant_names' => ['Defendant One'],
        'defendant_registries' => ['ZZ12345678'],
        'victim_name' => "Victim One\nVictim Two",
        'victim_registries' => ['VV12345678', 'VV22345678'],
        'victim_legal_rep' => 'Victim Rep One',
        'victim_legal_rep_registries' => ['VR12345678'],
        'witnesses' => 'Witness One',
        'witness_registries' => ['WW12345678'],
        'civil_plaintiff' => 'Civil Plaintiff One',
        'civil_plaintiff_registries' => ['CP12345678'],
        'civil_defendant' => 'Civil Defendant One',
        'civil_defendant_registries' => ['CF12345678'],
        'status' => 'scheduled',
    ]);
    $hearing->judges()->attach($judge->id, ['position' => 1]);

    app(HearingNotificationService::class)->send($hearing, 'created');

    Queue::assertPushed(SendEmongoliaNotificationJob::class, 9);

    foreach ([
        'AB12345678', // judge
        'CD12345678', // prosecutor
        'ZZ12345678', // defendant
        'VV12345678', // victim 1
        'VV22345678', // victim 2
        'VR12345678', // victim legal rep
        'WW12345678', // witness
        'CP12345678', // civil plaintiff
        'CF12345678', // civil defendant
    ] as $regnum) {
        Queue::assertPushed(
            SendEmongoliaNotificationJob::class,
            fn (SendEmongoliaNotificationJob $job) => $job->regnum === $regnum
        );
    }
});

it('runs notification jobs immediately when dispatch_sync is enabled', function () {
    config()->set('services.notification.enabled', true);
    config()->set('services.notification.access_token', 'fixed-access-token');
    config()->set('services.notification.notify_url', 'https://notification.mn/api/v1/notification');
    config()->set('services.notification.token_url', 'https://notification.mn/api/v1/external/token');
    config()->set('services.notification.username', 'user');
    config()->set('services.notification.password', 'pass');
    config()->set('services.notification.dispatch_sync', true);

    Http::fake([
        'notification.mn/api/v1/external/token' => Http::response(['token' => 'one-time'], 200),
        'notification.mn/api/v1/notification' => Http::response([
            'status' => 200,
            'Message' => 'Амжилттай',
            'RequestId' => 'req-sync',
        ], 200),
    ]);

    Role::firstOrCreate(['name' => 'judge', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'prosecutor', 'guard_name' => 'web']);

    $judge = User::factory()->create(['name' => 'Judge', 'register_number' => 'AB12345678']);
    $judge->assignRole('judge');

    $prosecutor = User::factory()->create(['name' => 'Prosecutor', 'register_number' => 'CD12345678']);
    $prosecutor->assignRole('prosecutor');

    $hearing = Hearing::create([
        'created_by' => $judge->id,
        'case_no' => '2026/200',
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

    $notifyRequests = collect(Http::recorded())
        ->filter(fn (array $pair) => str_contains($pair[0]->url(), '/api/v1/notification'))
        ->count();

    expect($notifyRequests)->toBe(2);
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

it('stores notification delivery logs in database', function () {
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
            'requestid' => 'req-uuid-db-123',
        ], 200),
    ]);

    $job = new SendEmongoliaNotificationJob(
        title: 'Шүүх хуралдааны зар товлогдлоо',
        body: ['Mail' => 'M', 'Messenger' => 'M', 'Notification' => 'N'],
        regnum: 'AB12345678',
        civilId: null,
        context: [
            'hearing_id' => 99,
            'action' => 'created',
            'role' => 'judge',
            'name' => 'Judge Name',
        ]
    );

    $job->handle(app(NotificationTokenService::class));

    $log = NotificationLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->delivery_status)->toBe('delivered')
        ->and($log->delivered)->toBeTrue()
        ->and($log->request_id)->toBe('req-uuid-db-123')
        ->and($log->recipient_role)->toBe('judge')
        ->and($log->recipient_name)->toBe('Judge Name')
        ->and($log->hearing_id)->toBe(99);
});
