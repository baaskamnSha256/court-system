<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

function createHearing(array $overrides = []): Hearing
{
    return Hearing::query()->create(array_merge([
        'title' => 'Admin notes handover hearing',
        'case_no' => '2026/ADMIN/NOTES/001',
        'start_at' => now()->addDay(),
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-1',
        'status' => 'scheduled',
        'defendants' => 'DEF-DEFAULT',
    ], $overrides));
}

it('filters pending hearings in admin notes list', function () {
    ensureRole('admin');
    ensureRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    $pendingNull = createHearing([
        'case_no' => 'ADMIN-PEND-NULL-001',
        'start_at' => now()->startOfMonth()->addDays(2),
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'notes_decision_status' => null,
        'defendants' => 'PENDING-NULL-HEARING',
    ]);

    $pendingUnknown = createHearing([
        'case_no' => 'ADMIN-PEND-UNKNOWN-001',
        'start_at' => now()->startOfMonth()->addDays(3),
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'notes_decision_status' => 'SomeOtherStatus',
        'defendants' => 'PENDING-UNKNOWN-HEARING',
    ]);

    $resolved = createHearing([
        'case_no' => 'ADMIN-RESOLVED-001',
        'start_at' => now()->startOfMonth()->addDays(4),
        'hearing_date' => now()->startOfMonth()->addDays(4)->toDateString(),
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'defendants' => 'RESOLVED-HEARING',
    ]);

    $outOfRange = createHearing([
        'case_no' => 'ADMIN-OUT-OF-RANGE-001',
        'start_at' => now()->subMonth()->addDays(2),
        'hearing_date' => now()->subMonth()->addDays(2)->toDateString(),
        'notes_decision_status' => null,
        'defendants' => 'OUT-OF-RANGE-HEARING',
    ]);

    // Silence unused var warnings (we only care about visibility in the HTML output).
    $resolvedId = $resolved->id;
    $outOfRangeId = $outOfRange->id;
    expect($pendingNull->id)->not->toBeEmpty();
    expect($pendingUnknown->id)->not->toBeEmpty();
    expect($resolvedId)->not->toBeEmpty();
    expect($outOfRangeId)->not->toBeEmpty();

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'notes_decision_status' => '__pending__',
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('PENDING-NULL-HEARING')
        ->assertSee('PENDING-UNKNOWN-HEARING')
        ->assertDontSee('RESOLVED-HEARING')
        ->assertDontSee('OUT-OF-RANGE-HEARING');
});

it('filters resolved hearings by exact status in admin notes list', function () {
    ensureRole('admin');
    ensureRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    createHearing([
        'case_no' => 'ADMIN-PEND-NULL-002',
        'start_at' => now()->startOfMonth()->addDays(2),
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'notes_decision_status' => null,
        'defendants' => 'RESOLVED-FILTER-EXCLUDE-PENDING',
    ]);

    createHearing([
        'case_no' => 'ADMIN-RESOLVED-002',
        'start_at' => now()->startOfMonth()->addDays(3),
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'defendants' => 'RESOLVED-FILTER-INCLUDE',
    ]);

    createHearing([
        'case_no' => 'ADMIN-PEND-UNKNOWN-002',
        'start_at' => now()->startOfMonth()->addDays(4),
        'hearing_date' => now()->startOfMonth()->addDays(4)->toDateString(),
        'notes_decision_status' => 'SomeOtherStatus',
        'defendants' => 'RESOLVED-FILTER-EXCLUDE-UNKNOWN',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'notes_decision_status' => 'Шийдвэрлэсэн',
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('RESOLVED-FILTER-INCLUDE')
        ->assertDontSee('RESOLVED-FILTER-EXCLUDE-PENDING')
        ->assertDontSee('RESOLVED-FILTER-EXCLUDE-UNKNOWN');
});

it('filters hearings by selected clerk in admin notes list', function () {
    ensureRole('admin');
    ensureRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $selectedClerk = User::factory()->create();
    $selectedClerk->assignRole('court_clerk');

    $otherClerk = User::factory()->create();
    $otherClerk->assignRole('court_clerk');

    createHearing([
        'case_no' => 'ADMIN-CLERK-FILTER-001',
        'clerk_id' => $selectedClerk->id,
        'defendants' => 'CLERK-FILTER-INCLUDE',
    ]);

    createHearing([
        'case_no' => 'ADMIN-CLERK-FILTER-002',
        'clerk_id' => $otherClerk->id,
        'defendants' => 'CLERK-FILTER-EXCLUDE',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'clerk_id' => $selectedClerk->id,
        ]))
        ->assertOk()
        ->assertSee('CLERK-FILTER-INCLUDE')
        ->assertDontSee('CLERK-FILTER-EXCLUDE');
});
