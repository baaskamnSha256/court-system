<?php

use App\Models\Hearing;
use App\Models\User;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;
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

it('does not show reports section for court clerk', function () {
    ensureRole('court_clerk');

    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $this->actingAs($clerk)
        ->get(route('court_clerk.dashboard'))
        ->assertOk()
        ->assertDontSee('>Тайлан<', false);

    $this->actingAs($clerk)
        ->get('/court-clerk/reports')
        ->assertNotFound();
});

it('shows court clerk hearings list with simplified titles', function () {
    ensureRole('court_clerk');

    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $ownHearing = createHearing([
        'case_no' => 'CC-OWN-001',
        'start_at' => now()->addDay(),
        'clerk_id' => $clerk->id,
    ]);

    $this->actingAs($clerk)
        ->get(route('court_clerk.hearings.index'))
        ->assertOk()
        ->assertSee('Хурлын зар')
        ->assertSee('CC-OWN-001')
        ->assertDontSee('Миний хариуцсан хурлын зарууд')
        ->assertDontSee('Хурлын зар (Шүүх хурлын нарийн бичгийн дарга)');
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

it('shows role-scoped total scheduled hearings on judge dashboard', function () {
    ensureRole('judge');
    ensureRole('admin');

    $judge = User::factory()->create();
    $otherJudge = User::factory()->create();
    $judge->assignRole('judge');
    $otherJudge->assignRole('judge');

    $ownHearing = createHearing([
        'case_no' => 'J-TOTAL-OWN',
        'start_at' => now()->copy()->startOfDay(),
        'hearing_date' => now()->toDateString(),
    ]);
    $otherHearing = createHearing([
        'case_no' => 'J-TOTAL-OTHER',
        'start_at' => now()->copy()->startOfDay()->addHour(),
        'hearing_date' => now()->toDateString(),
    ]);

    $ownHearing->judges()->attach($judge->id, ['position' => 1]);
    $otherHearing->judges()->attach($otherJudge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.dashboard'))
        ->assertOk()
        ->assertSee('Нийт зарлагдсан шүүх хурал')
        ->assertViewHas('totalScheduledHearings', 1);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('totalScheduledHearings', 2);
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

it('shows prosecutor hearings list title and decision columns when status filter is active', function () {
    ensureRole('prosecutor');

    $prosecutor = User::factory()->create();
    $prosecutor->assignRole('prosecutor');

    createHearing([
        'case_no' => 'P-DEC-COLS-001',
        'start_at' => now()->copy()->startOfDay(),
        'prosecutor_ids' => [$prosecutor->id],
        'notes_handover_text' => 'Прокурорын тойм',
        'notes_decided_matter' => 'Зүйл Y',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $this->actingAs($prosecutor)
        ->get(route('prosecutor.hearings.index'))
        ->assertOk()
        ->assertSee('Миний хурлын зарууд')
        ->assertDontSee('Хурлын зар (Прокурор)')
        ->assertDontSee('Шийдвэрийн тойм');

    $this->actingAs($prosecutor)
        ->get(route('prosecutor.hearings.index', ['notes_decision_status' => 'Шийдвэрлэсэн']))
        ->assertOk()
        ->assertSee('Шийдвэрийн тойм')
        ->assertSee('Шийдвэрийн төрөл')
        ->assertSee('Шийдвэрлэсэн зүйл анги')
        ->assertSee('Прокурорын тойм')
        ->assertSee('Зүйл Y')
        ->assertSee('Шийдвэрлэсэн');
});

it('shows lawyer hearings list title and decision columns when status filter is active', function () {
    ensureRole('lawyer');

    $lawyer = User::factory()->create(['name' => 'Lawyer Decision']);
    $lawyer->assignRole('lawyer');

    $hearing = createHearing([
        'case_no' => 'L-DEC-COLS-001',
        'start_at' => now()->copy()->startOfDay(),
        'defendant_lawyers_text' => [$lawyer->name],
        'notes_handover_text' => 'Өмгөөлөгчийн тойм',
        'notes_decided_matter' => 'Зүйл X',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $this->actingAs($lawyer)
        ->get(route('lawyer.hearings.index'))
        ->assertOk()
        ->assertSee('Миний хурлын зарууд')
        ->assertDontSee('Хурлын зар (Өмгөөлөгч)')
        ->assertDontSee('Шийдвэрийн тойм');

    $this->actingAs($lawyer)
        ->get(route('lawyer.hearings.index', ['notes_decision_status' => 'Шийдвэрлэсэн']))
        ->assertOk()
        ->assertSee('Шийдвэрийн тойм')
        ->assertSee('Шийдвэрийн төрөл')
        ->assertSee('Шийдвэрлэсэн зүйл анги')
        ->assertSee('Өмгөөлөгчийн тойм')
        ->assertSee('Зүйл X')
        ->assertSee('Шийдвэрлэсэн');
});

it('shows judge hearings list title and decision columns when status filter is active', function () {
    ensureRole('judge');

    $judge = User::factory()->create();
    $judge->assignRole('judge');

    $hearing = createHearing([
        'case_no' => 'J-DEC-COLS-001',
        'start_at' => now()->copy()->startOfDay(),
        'notes_handover_text' => 'Шийдвэрийн тойм текст',
        'notes_decided_matter' => 'Зүйл А, Зүйл Б',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);
    $hearing->judges()->attach($judge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.hearings.index'))
        ->assertOk()
        ->assertSee('Миний хурлын зарууд')
        ->assertDontSee('Миний оролцох хурлын зарууд')
        ->assertDontSee('Хурлын зар (Шүүгч)')
        ->assertDontSee('Шийдвэрийн тойм');

    $this->actingAs($judge)
        ->get(route('judge.hearings.index', ['notes_decision_status' => 'Шийдвэрлэсэн']))
        ->assertOk()
        ->assertSee('Шийдвэрийн тойм')
        ->assertSee('Шийдвэрийн төрөл')
        ->assertSee('Шийдвэрлэсэн зүйл анги')
        ->assertSee('Шийдвэрийн тойм текст')
        ->assertSee('Зүйл А')
        ->assertSee('Зүйл Б')
        ->assertSee('Шийдвэрлэсэн');
});

it('lists hearings from year start when opened from dashboard decision card', function () {
    Carbon::setTestNow('2026-05-19');
    ensureRole('judge');

    $judge = User::factory()->create();
    $judge->assignRole('judge');

    $earlyResolved = createHearing([
        'case_no' => 'J-DASH-YEAR-001',
        'hearing_date' => '2026-02-15',
        'start_at' => '2026-02-15 10:00:00',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);
    $earlyResolved->judges()->attach($judge->id, ['position' => 1]);

    $this->actingAs($judge)
        ->get(route('judge.hearings.index', array_merge(
            HearingDashboardStatistics::dashboardDecisionFilterQuery(),
            ['notes_decision_status' => 'Шийдвэрлэсэн']
        )))
        ->assertOk()
        ->assertSee('J-DASH-YEAR-001')
        ->assertSee('Шийдвэрийн тойм');

    Carbon::setTestNow();
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
