<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureRoleExists(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

function createClerkHearing(int $clerkId, array $overrides = []): Hearing
{
    return Hearing::query()->create(array_merge([
        'title' => 'Court clerk notes hearing',
        'case_no' => '2026/CLERK/NOTES/001',
        'start_at' => now()->addDay(),
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-1',
        'status' => 'scheduled',
        'clerk_id' => $clerkId,
        'defendants' => 'DEF-DEFAULT',
    ], $overrides));
}

it('filters issued hearings when notes_handover_issued equals 1', function () {
    ensureRoleExists('court_clerk');

    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    createClerkHearing($clerk->id, [
        'notes_handover_issued' => true,
        'defendants' => 'ISSUED-HEARING-ROW',
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(2),
    ]);
    createClerkHearing($clerk->id, [
        'notes_handover_issued' => false,
        'defendants' => 'PENDING-HEARING-ROW',
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($clerk)
        ->get(route('court_clerk.notes.index', [
            'notes_handover_issued' => 1,
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('ISSUED-HEARING-ROW')
        ->assertDontSee('PENDING-HEARING-ROW');
});

it('filters pending hearings when notes_handover_issued equals 0', function () {
    ensureRoleExists('court_clerk');

    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    createClerkHearing($clerk->id, [
        'notes_handover_issued' => true,
        'defendants' => 'ISSUED-HEARING-ROW-2',
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(2),
    ]);
    createClerkHearing($clerk->id, [
        'notes_handover_issued' => false,
        'defendants' => 'PENDING-HEARING-ROW-2',
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'start_at' => now()->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($clerk)
        ->get(route('court_clerk.notes.index', [
            'notes_handover_issued' => 0,
            'hearing_date_from' => $from,
            'hearing_date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('PENDING-HEARING-ROW-2')
        ->assertDontSee('ISSUED-HEARING-ROW-2');
});
