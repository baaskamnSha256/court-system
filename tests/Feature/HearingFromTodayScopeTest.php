<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('excludes past hearings from judge hearings index', function () {
    Role::query()->firstOrCreate(['name' => 'judge', 'guard_name' => 'web']);

    $judge = User::factory()->create();
    $judge->assignRole('judge');

    $past = Hearing::query()->create([
        'title' => 'Past hearing',
        'case_no' => 'PAST-001',
        'hearing_date' => now()->subDay()->toDateString(),
        'start_at' => now()->subDay(),
        'status' => 'scheduled',
    ]);
    $upcoming = Hearing::query()->create([
        'title' => 'Upcoming hearing',
        'case_no' => 'FUTURE-001',
        'hearing_date' => now()->toDateString(),
        'start_at' => now()->copy()->startOfDay()->addHours(2),
        'status' => 'scheduled',
    ]);

    $past->judges()->attach($judge->id, ['position' => 1]);
    $upcoming->judges()->attach($judge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.hearings.index'))
        ->assertOk()
        ->assertSee('FUTURE-001')
        ->assertDontSee('PAST-001');
});

it('excludes past hearings from admin hearings index', function () {
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hearing::query()->create([
        'title' => 'Past admin hearing',
        'case_no' => 'ADM-PAST-001',
        'hearing_date' => now()->subDays(3)->toDateString(),
        'start_at' => now()->subDays(3),
        'status' => 'scheduled',
    ]);

    Hearing::query()->create([
        'title' => 'Upcoming admin hearing',
        'case_no' => 'ADM-FUTURE-001',
        'hearing_date' => now()->addDay()->toDateString(),
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.hearings.index'))
        ->assertOk()
        ->assertSee('ADM-FUTURE-001')
        ->assertDontSee('ADM-PAST-001');
});
