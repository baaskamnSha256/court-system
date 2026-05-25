<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminNotesEnsureRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

function adminNotesCreateHearing(array $overrides = []): Hearing
{
    return Hearing::query()->create(array_merge([
        'title' => 'Admin notes hearing',
        'case_no' => '2026/ADMIN/NOTES/001',
        'start_at' => now()->startOfMonth()->addDays(2),
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-1',
        'status' => 'scheduled',
        'defendants' => 'DEF-DEFAULT',
    ], $overrides));
}

it('shows only issued hearings when admin clicks the Тэмдэглэл хүлээлцсэн card', function () {
    adminNotesEnsureRole('admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    adminNotesCreateHearing([
        'notes_handover_issued' => true,
        'defendants' => 'ADMIN-ISSUED-ROW',
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(2),
    ]);
    adminNotesCreateHearing([
        'notes_handover_issued' => false,
        'defendants' => 'ADMIN-PENDING-ROW',
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'notes_handover_issued' => 1,
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('ADMIN-ISSUED-ROW')
        ->assertDontSee('ADMIN-PENDING-ROW');
});

it('shows only pending hearings when admin clicks the Тэмдэглэл хүлээлцээгүй card', function () {
    adminNotesEnsureRole('admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    adminNotesCreateHearing([
        'notes_handover_issued' => true,
        'defendants' => 'ADMIN-ISSUED-ROW-2',
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(2),
    ]);
    adminNotesCreateHearing([
        'notes_handover_issued' => false,
        'defendants' => 'ADMIN-PENDING-ROW-2',
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'notes_handover_issued' => 0,
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('ADMIN-PENDING-ROW-2')
        ->assertDontSee('ADMIN-ISSUED-ROW-2');
});

it('falls back to pending-only when admin opens the listing without the issued filter', function () {
    adminNotesEnsureRole('admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    adminNotesCreateHearing([
        'notes_handover_issued' => true,
        'defendants' => 'ADMIN-ISSUED-DEFAULT',
    ]);
    adminNotesCreateHearing([
        'notes_handover_issued' => false,
        'defendants' => 'ADMIN-PENDING-DEFAULT',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index'))
        ->assertOk()
        ->assertSee('ADMIN-PENDING-DEFAULT')
        ->assertDontSee('ADMIN-ISSUED-DEFAULT');
});
