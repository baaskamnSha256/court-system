<?php

use App\Models\Hearing;
use App\Models\User;
use App\Support\HearingDashboardStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureNotesListingRole(string $name): void
{
    Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

it('lists non-resolved hearings from 2026-01-01 onward on admin notes handover index', function () {
    ensureNotesListingRole('admin');
    ensureNotesListingRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $before2026 = Hearing::query()->create([
        'title' => 'Before 2026',
        'case_no' => 'NOTES-BEFORE-2026',
        'hearing_date' => '2025-12-20',
        'start_at' => '2025-12-20 10:00:00',
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Хойшилсон',
        'defendants' => 'BEFORE-2026-DEF',
    ]);

    $resolvedInRange = Hearing::query()->create([
        'title' => 'Resolved in range',
        'case_no' => 'NOTES-RESOLVED-IN-RANGE',
        'hearing_date' => '2026-02-10',
        'start_at' => '2026-02-10 10:00:00',
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'defendants' => 'RESOLVED-IN-RANGE-DEF',
    ]);

    $postponedInRange = Hearing::query()->create([
        'title' => 'Postponed in range',
        'case_no' => 'NOTES-POSTPONED-IN-RANGE',
        'hearing_date' => '2026-03-15',
        'start_at' => '2026-03-15 11:00:00',
        'hour' => 11,
        'minute' => 0,
        'courtroom' => 'B',
        'notes_decision_status' => 'Хойшилсон',
        'defendants' => 'POSTPONED-IN-RANGE-DEF',
    ]);

    expect($before2026->id)->not->toBeEmpty()
        ->and($resolvedInRange->id)->not->toBeEmpty()
        ->and($postponedInRange->id)->not->toBeEmpty();

    $this->actingAs($admin)
        ->get(route('admin.notes.index'))
        ->assertOk()
        ->assertSee('POSTPONED-IN-RANGE-DEF')
        ->assertDontSee('RESOLVED-IN-RANGE-DEF')
        ->assertDontSee('BEFORE-2026-DEF');
});

it('still allows filtering resolved hearings explicitly on admin notes handover index', function () {
    ensureNotesListingRole('admin');
    ensureNotesListingRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hearing::query()->create([
        'title' => 'Resolved filter',
        'case_no' => 'NOTES-RESOLVED-FILTER',
        'hearing_date' => '2026-04-01',
        'start_at' => '2026-04-01 09:00:00',
        'hour' => 9,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'defendants' => 'RESOLVED-FILTER-DEF',
    ]);

    Hearing::query()->create([
        'title' => 'Other status',
        'case_no' => 'NOTES-OTHER-STATUS',
        'hearing_date' => '2026-04-02',
        'start_at' => '2026-04-02 09:00:00',
        'hour' => 9,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Түдгэлзүүлсэн',
        'defendants' => 'OTHER-STATUS-DEF',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notes.index', [
            'notes_decision_status' => 'Шийдвэрлэсэн',
        ]))
        ->assertOk()
        ->assertSee('RESOLVED-FILTER-DEF')
        ->assertDontSee('OTHER-STATUS-DEF');
});

it('scopes hearings from scheduled since date on the model', function () {
    Hearing::query()->create([
        'title' => 'Old',
        'case_no' => 'SCOPE-OLD',
        'hearing_date' => '2025-06-01',
        'start_at' => '2025-06-01 10:00:00',
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
    ]);

    $inRange = Hearing::query()->create([
        'title' => 'New',
        'case_no' => 'SCOPE-NEW',
        'hearing_date' => '2026-01-15',
        'start_at' => '2026-01-15 10:00:00',
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
    ]);

    $ids = Hearing::query()->fromScheduledSince()->pluck('id')->all();

    expect($ids)->toContain($inRange->id)
        ->and(HearingDashboardStatistics::DECISION_SUMMARY_SCHEDULED_SINCE)->toBe('2026-01-01');
});
