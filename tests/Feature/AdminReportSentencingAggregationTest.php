<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function ensureReportRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('shows punishment and article aggregation rows on admin report', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-2.2', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 33.1-6.4', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-001',
        'title' => 'Report hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч тайлан',
                'decided_matter_ids' => [$m1->id, $m2->id],
                'punishments' => [
                    'fine' => ['fine_units' => 1000, 'damage_amount' => 500],
                    'imprisonment_open' => ['years' => 1, 'months' => 0],
                ],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл')
        ->assertSee('Excel-тэй ижил багана')
        ->assertSee('Хэргийн дугаар')
        ->assertSee('Шүүх хуралдааны шийдвэр')
        ->assertSee('Шийдвэрлэсэн зүйл анги');

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'article',
        ]))
        ->assertOk()
        ->assertSee('Шийдвэрлэсэн зүйл анги')
        ->assertSee('ЭХТА 10.1-2.2')
        ->assertSee('ЭХТА 33.1-6.4');
});

it('shows saved sentence decision per defendant in report detail table', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-DECISION-001',
        'title' => 'Decision per defendant',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'А шүүгдэгч',
                'outcome_track' => 'termination',
                'termination_kind' => 'acquit',
                'termination_note' => 'Цагаатгасан',
                'decided_matter_ids' => [],
                'punishments' => [],
            ],
            [
                'defendant_name' => 'Б шүүгдэгч',
                'outcome_track' => 'no_sentence',
                'special_outcome' => 'Эрүүгийн хариуцлагаас чөлөөлсөн',
                'decided_matter_ids' => [],
                'punishments' => [],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('А шүүгдэгч')
        ->assertSee('Цагаатгах')
        ->assertSee('Б шүүгдэгч')
        ->assertSee('Эрүүгийн хариуцлагаас чөлөөлсөн');
});

it('includes only decided hearings in defendant detail report table', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-NOT-DECIDED-001',
        'title' => 'Not decided hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 11,
        'minute' => 30,
        'courtroom' => 'B',
        'notes_decision_status' => 'Хойшилсон',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Тайлан руу орохгүй шүүгдэгч',
                'defendant_registry' => 'УУ01010111',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [],
                'punishments' => [],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertDontSee('Тайлан руу орохгүй шүүгдэгч');
});

it('uses per-article allocations for punishment x article statistics', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 11.6', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 12.1', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-ALLOC-001',
        'title' => 'Allocation report',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч тайлан C',
                'decided_matter_ids' => [$m1->id, $m2->id],
                'punishments' => [
                    'fine' => ['fine_units' => 2000, 'damage_amount' => 500000],
                ],
                'allocations' => [
                    [
                        'matter_category_id' => $m1->id,
                        'punishments' => [
                            'fine' => ['fine_units' => 1200, 'damage_amount' => 300000],
                        ],
                    ],
                    [
                        'matter_category_id' => $m2->id,
                        'punishments' => [
                            'fine' => ['fine_units' => 800, 'damage_amount' => 200000],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл');
});

it('opens each report submenu tab', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $baseQuery = [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->endOfMonth()->format('Y-m-d'),
    ];

    actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Тайлангийн төрлийг сонгоод тус бүрийн дэлгэрэнгүй рүү орно.')
        ->assertSee('Тэмдэглэл хүлээлцэх')
        ->assertDontSee('Excel татах');

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, ['tab' => 'notes_handover'])))
        ->assertOk()
        ->assertSee('Тэмдэглэл хүлээлцсэн')
        ->assertDontSee('Шүүх хуралдааны шийдвэрийн тойм');

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, ['tab' => 'decision_summary'])))
        ->assertOk()
        ->assertSee('Шүүх хуралдааны шийдвэрийн тойм');

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, ['tab' => 'article'])))
        ->assertOk()
        ->assertSee('Шийдвэрлэсэн зүйл анги');

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($baseQuery, ['tab' => 'punishment'])))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл');
});

it('ignores clerk_id for article and punishment report tabs', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $clerkA = User::factory()->create();
    $clerkB = User::factory()->create();
    $clerkA->assignRole('court_clerk');
    $clerkB->assignRole('court_clerk');

    $mArticleA = MatterCategory::query()->create(['name' => 'ЭХТА Клерк шүүлт A', 'sort_order' => 1]);
    $mArticleB = MatterCategory::query()->create(['name' => 'ЭХТА Клерк шүүлт B', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'clerk_id' => $clerkA->id,
        'case_no' => 'R-CLERK-A',
        'title' => 'Hearing A',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Д1',
                'decided_matter_ids' => [$mArticleA->id],
                'punishments' => ['fine' => ['fine_units' => 100, 'damage_amount' => 0]],
            ],
        ],
    ]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'clerk_id' => $clerkB->id,
        'case_no' => 'R-CLERK-B',
        'title' => 'Hearing B',
        'hearing_date' => now()->toDateString(),
        'hour' => 11,
        'minute' => 0,
        'courtroom' => 'B',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Д2',
                'decided_matter_ids' => [$mArticleB->id],
                'punishments' => ['imprisonment_open' => ['years' => 0, 'months' => 6]],
            ],
        ],
    ]);

    $range = [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->endOfMonth()->format('Y-m-d'),
    ];

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($range, [
            'tab' => 'article',
            'clerk_id' => $clerkA->id,
        ])))
        ->assertOk()
        ->assertSee('ЭХТА Клерк шүүлт A')
        ->assertSee('ЭХТА Клерк шүүлт B');

    actingAs($admin)
        ->get(route('admin.reports.index', array_merge($range, [
            'tab' => 'punishment',
            'clerk_id' => $clerkA->id,
        ])))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл');
});

it('counts special outcomes separately from punishments', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 20.1', 'sort_order' => 1]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-SPECIAL-001',
        'title' => 'Special outcome hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч тусгай',
                'decided_matter_ids' => [$m1->id],
                'punishments' => [],
                'special_outcome' => 'Эрүүгийн хариуцлагаас чөлөөлсөн',
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл')
        ->assertSee('Excel-тэй ижил багана');
});

it('shows age and gender sentencing counts from defendant registry numbers', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 25.1', 'sort_order' => 1]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-AGE-001',
        'title' => 'Age gender report hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Эмэгтэй шүүгдэгч',
                'defendant_registry' => 'ЙС96072608',
                'decided_matter_ids' => [$m1->id],
                'punishments' => [
                    'fine' => ['fine_units' => 100, 'damage_amount' => 0],
                ],
            ],
            [
                'defendant_name' => 'Эрэгтэй шүүгдэгч',
                'defendant_registry' => 'ЙС96072619',
                'decided_matter_ids' => [$m1->id],
                'punishments' => [
                    'community_service' => ['hours' => 120],
                ],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл');
});

it('downloads searchable defendant detail report file', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $matter = MatterCategory::query()->create(['name' => 'ЭХТА 30.2', 'sort_order' => 1]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-DETAIL-001',
        'title' => 'Detail export hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 9,
        'minute' => 30,
        'courtroom' => 'D',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч экспорт',
                'defendant_registry' => 'АА99112211',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$matter->id],
                'punishments' => [
                    'community_service' => ['hours' => 240],
                ],
            ],
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.reports.download.defendant-details', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
