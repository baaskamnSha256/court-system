<?php

use App\Models\Hearing;
use App\Models\User;
use App\Services\Notifications\HearingNotificationPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('builds payload with hearing participants', function () {
    Role::firstOrCreate(['name' => 'judge', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'prosecutor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'lawyer', 'guard_name' => 'web']);

    $judge = User::factory()->create([
        'name' => 'Judge One',
        'phone' => '99110011',
        'register_number' => 'AB12345678',
    ]);
    $judge->assignRole('judge');

    $prosecutor = User::factory()->create([
        'name' => 'Prosecutor One',
        'phone' => '99110012',
        'register_number' => 'CD12345678',
    ]);
    $prosecutor->assignRole('prosecutor');

    $lawyer = User::factory()->create([
        'name' => 'Lawyer One',
        'phone' => '99110013',
        'register_number' => 'EF12345678',
    ]);
    $lawyer->assignRole('lawyer');

    $hearing = Hearing::create([
        'created_by' => $judge->id,
        'case_no' => '2026/001',
        'title' => 'Хэвийн',
        'hearing_state' => 'Хэвийн',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 30,
        'start_at' => now()->setTime(10, 30),
        'end_at' => now()->setTime(11, 0),
        'duration_minutes' => 30,
        'courtroom' => 'A',
        'preventive_measure' => 'Цагдан хорих',
        'prosecutor_id' => $prosecutor->id,
        'prosecutor_ids' => [$prosecutor->id],
        'defendant_names' => ['Defendant'],
        'defendant_lawyers_text' => ['Lawyer One'],
        'status' => 'scheduled',
    ]);
    $hearing->judges()->attach($judge->id, ['position' => 1]);

    $payload = app(HearingNotificationPayloadBuilder::class)->build($hearing, 'created');

    expect($payload['title'])->toBe('Шүүх хурал товлогдлоо')
        ->and($payload['message'])->toContain('Хэрэг № 2026/001')
        ->and($payload['recipients'])->toHaveCount(3)
        ->and(collect($payload['recipients'])->pluck('name')->all())
        ->toContain('Judge One', 'Prosecutor One', 'Lawyer One');
});
