<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureDecisionReportRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('lists hearings on decision summary tab when a status card is selected', function () {
    ensureDecisionReportRole('admin');
    ensureDecisionReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    Hearing::query()->create([
        'title' => 'Pending null',
        'case_no' => 'REP-PEND-NULL',
        'start_at' => now()->startOfMonth()->addDays(2),
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-1',
        'status' => 'scheduled',
        'defendants' => 'REP-PENDING-NULL-DEF',
        'notes_decision_status' => null,
    ]);

    Hearing::query()->create([
        'title' => 'Resolved',
        'case_no' => 'REP-RESOLVED',
        'start_at' => now()->startOfMonth()->addDays(3),
        'hearing_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'hour' => 11,
        'minute' => 0,
        'courtroom' => 'A-2',
        'status' => 'scheduled',
        'defendants' => 'REP-RESOLVED-DEF',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $baseQuery = [
        'tab' => 'decision_summary',
        'date_from' => $from,
        'date_to' => $to,
    ];

    $this->actingAs($admin)
        ->get(route('admin.reports.index', $baseQuery))
        ->assertOk()
        ->assertSee('Хурлын жагсаалт')
        ->assertSee('Шийдвэрийн тойм')
        ->assertSee('Шүүх хуралдааны шийдвэр')
        ->assertSee('REP-PENDING-NULL-DEF')
        ->assertSee('REP-RESOLVED-DEF');

    $this->actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, [
            'notes_decision_status' => '__pending__',
        ])))
        ->assertOk()
        ->assertSee('Шийдвэр: Хүлээгдэж буй')
        ->assertSee('Шүүх бүрэлдэхүүн болон шүүгчийн нэр')
        ->assertSee('Хэргийн дугаар')
        ->assertSee('Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч')
        ->assertSee('REP-PENDING-NULL-DEF')
        ->assertDontSee('REP-RESOLVED-DEF')
        ->assertSee('notes_decision_status=__pending__', false);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, [
            'notes_decision_status' => 'Шийдвэрлэсэн',
        ])))
        ->assertOk()
        ->assertSee('Шийдвэр: Шийдвэрлэсэн')
        ->assertSee('REP-RESOLVED-DEF')
        ->assertDontSee('REP-PENDING-NULL-DEF');
});

it('shows total scheduled hearings for the selected date range on decision summary tab', function () {
    ensureDecisionReportRole('admin');
    ensureDecisionReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    Hearing::query()->create([
        'title' => 'Out of range',
        'case_no' => 'REP-OUT-RANGE',
        'start_at' => now()->subMonth()->toDateString().' 10:00:00',
        'hearing_date' => now()->subMonth()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-0',
        'status' => 'scheduled',
        'defendants' => 'OUT-RANGE',
    ]);

    Hearing::query()->create([
        'title' => 'In range',
        'case_no' => 'REP-IN-RANGE',
        'start_at' => now()->startOfMonth()->addDays(2)->toDateString().' 10:00:00',
        'hearing_date' => now()->startOfMonth()->addDays(2)->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A-1',
        'status' => 'scheduled',
        'defendants' => 'IN-RANGE',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'tab' => 'decision_summary',
            'date_from' => $from,
            'date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('Нийт зарлагдсан шүүх хурал')
        ->assertSee($from)
        ->assertSee($to)
        ->assertSee('IN-RANGE')
        ->assertDontSee('OUT-RANGE');
});

it('links decision summary cards to the reports tab with status filter', function () {
    ensureDecisionReportRole('admin');
    ensureDecisionReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->format('Y-m-d');
    $to = now()->endOfMonth()->format('Y-m-d');

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'tab' => 'decision_summary',
            'date_from' => $from,
            'date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('notes_decision_status=__pending__', false)
        ->assertSee('notes_decision_status', false)
        ->assertSee('tab=decision_summary', false);
});
