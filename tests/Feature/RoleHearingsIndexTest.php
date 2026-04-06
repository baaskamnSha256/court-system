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
        'title' => 'Role hearing',
        'case_no' => '2026/ROLE/001',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
    ], $overrides));
}

it('shows only own hearings for judge hearings list', function () {
    ensureRole('judge');

    $judge = User::factory()->create();
    $otherJudge = User::factory()->create();
    $judge->assignRole('judge');
    $otherJudge->assignRole('judge');

    $ownHearing = createHearing(['case_no' => 'J-OWN-001']);
    $otherHearing = createHearing(['case_no' => 'J-OTHER-001']);

    $ownHearing->judges()->attach($judge->id, ['position' => 1]);
    $otherHearing->judges()->attach($otherJudge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.hearings.index'))
        ->assertOk()
        ->assertSee('J-OWN-001')
        ->assertDontSee('J-OTHER-001');
});

it('shows only own hearings for prosecutor hearings list', function () {
    ensureRole('prosecutor');

    $prosecutor = User::factory()->create();
    $otherProsecutor = User::factory()->create();
    $prosecutor->assignRole('prosecutor');
    $otherProsecutor->assignRole('prosecutor');

    createHearing([
        'case_no' => 'P-OWN-001',
        'prosecutor_ids' => [$prosecutor->id],
    ]);

    createHearing([
        'case_no' => 'P-OTHER-001',
        'prosecutor_ids' => [$otherProsecutor->id],
    ]);

    $this->actingAs($prosecutor)
        ->get(route('prosecutor.hearings.index'))
        ->assertOk()
        ->assertSee('P-OWN-001')
        ->assertDontSee('P-OTHER-001');
});

it('shows only own hearings for lawyer hearings list', function () {
    ensureRole('lawyer');

    $lawyer = User::factory()->create(['name' => 'Lawyer One']);
    $otherLawyer = User::factory()->create(['name' => 'Lawyer Two']);
    $lawyer->assignRole('lawyer');
    $otherLawyer->assignRole('lawyer');

    createHearing([
        'case_no' => 'L-OWN-001',
        'defendant_lawyers_text' => [$lawyer->name],
    ]);

    createHearing([
        'case_no' => 'L-OTHER-001',
        'defendant_lawyers_text' => [$otherLawyer->name],
    ]);

    $this->actingAs($lawyer)
        ->get(route('lawyer.hearings.index'))
        ->assertOk()
        ->assertSee('L-OWN-001')
        ->assertDontSee('L-OTHER-001');
});

it('shows pending decision count for judge dashboard', function () {
    ensureRole('judge');

    $judge = User::factory()->create();
    $judge->assignRole('judge');

    $pendingHearing = createHearing([
        'case_no' => 'J-PENDING-001',
        'start_at' => now()->copy()->startOfDay(),
        'notes_decision_status' => null,
    ]);
    $resolvedHearing = createHearing([
        'case_no' => 'J-RESOLVED-001',
        'start_at' => now()->copy()->startOfDay()->addHour(),
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $pendingHearing->judges()->attach($judge->id, ['position' => 1]);
    $resolvedHearing->judges()->attach($judge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.dashboard'))
        ->assertOk()
        ->assertViewHas('decisionCounts', fn (array $counts) => ($counts['Хүлээгдэж буй'] ?? 0) === 1)
        ->assertViewHas('decisionCounts', fn (array $counts) => ($counts['Шийдвэрлэсэн'] ?? 0) === 1);
});

it('filters hearings by pending decision status', function () {
    ensureRole('judge');

    $judge = User::factory()->create();
    $judge->assignRole('judge');

    $pending = createHearing([
        'case_no' => 'J-PEND-FILTER-001',
        'notes_decision_status' => null,
    ]);
    $resolved = createHearing([
        'case_no' => 'J-PEND-FILTER-002',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $pending->judges()->attach($judge->id, ['position' => 1]);
    $resolved->judges()->attach($judge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.hearings.index', ['notes_decision_status' => '__pending__']))
        ->assertOk()
        ->assertSee('J-PEND-FILTER-001')
        ->assertDontSee('J-PEND-FILTER-002');
});
