<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Services\Reports\DefendantDetailReportService;
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
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй тайлан')
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
        ->assertSee('ЭХТА 33.1-6.4')
        ->assertSee('Цагаатгах')
        ->assertSee('Торгох (нэгж)')
        ->assertDontSee('>Нийт<', false);
});

it('does not copy first defendant hearing-level fine onto other defendants in detail rows', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-PER-DEF-FINE-001',
        'title' => 'Per-defendant fine',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_fine_units' => '9999',
        'notes_damage_amount' => '8888',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Эхний шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [],
                'punishments' => [
                    'fine' => ['fine_units' => 5000],
                    'damage_amount' => 1111,
                ],
            ],
            [
                'defendant_name' => 'Хоёр дахь шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [],
                'punishments' => [],
            ],
        ],
    ]);

    $service = app(DefendantDetailReportService::class);
    $rows = $service->buildRows(Hearing::query()->where('case_no', 'R-PER-DEF-FINE-001'));

    $first = collect($rows)->firstWhere('defendant_name', 'Эхний шүүгдэгч');
    $second = collect($rows)->firstWhere('defendant_name', 'Хоёр дахь шүүгдэгч');

    expect($first)->not->toBeNull()
        ->and($first['fine_units'])->toBe('5000')
        ->and($first['damage_amount'])->toBe('1111')
        ->and($second)->not->toBeNull()
        ->and($second['fine_units'])->toBe('')
        ->and($second['damage_amount'])->toBe('');
});

it('emits one defendant detail row per decided matter with per-article punishments', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-ROW-SPLIT-A', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-ROW-SPLIT-B', 'sort_order' => 2]);
    $m3 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-ROW-SPLIT-C', 'sort_order' => 3]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-MULTI-MATTER-ROWS-001',
        'title' => 'Multi matter rows',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'matter_category_ids' => [$m1->id, $m2->id, $m3->id],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Нэг шүүгдэгч олон зүйл',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id, $m3->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m3->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => [],
                'allocations' => [
                    [
                        'matter_category_id' => $m1->id,
                        'punishments' => ['fine' => ['fine_units' => 100]],
                    ],
                    [
                        'matter_category_id' => $m2->id,
                        'punishments' => ['fine' => ['fine_units' => 200]],
                    ],
                    [
                        'matter_category_id' => $m3->id,
                        'punishments' => ['fine' => ['fine_units' => 300]],
                    ],
                ],
            ],
        ],
    ]);

    $service = app(DefendantDetailReportService::class);
    $rows = $service->buildRows(Hearing::query()->where('case_no', 'R-MULTI-MATTER-ROWS-001'));

    expect($rows)->toHaveCount(3)
        ->and(collect($rows)->pluck('defendant_name')->unique()->values()->all())->toBe(['Нэг шүүгдэгч олон зүйл'])
        ->and(collect($rows)->pluck('decided_matter')->sort()->values()->all())->toBe([
            'ЭХТА 10.1-ROW-SPLIT-A',
            'ЭХТА 10.1-ROW-SPLIT-B',
            'ЭХТА 10.1-ROW-SPLIT-C',
        ])
        ->and(collect($rows)->pluck('fine_units')->sort()->values()->all())->toBe(['100', '200', '300'])
        ->and(collect($rows)->pluck('incoming_matter')->unique()->values()->all())->toBe([
            'ЭХТА 10.1-ROW-SPLIT-A, ЭХТА 10.1-ROW-SPLIT-B, ЭХТА 10.1-ROW-SPLIT-C',
        ]);
});

it('emits one row per matter from matter_decisions when decided_matter_ids is empty', function () {
    ensureReportRole('admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА MD-1', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА MD-2', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-MATTER-DECISIONS-001',
        'title' => 'Matter decisions rows',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'matter_category_ids' => [$m1->id, $m2->id],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч MD',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'dismiss'],
                ],
                'punishments' => ['fine' => ['fine_units' => 999]],
                'allocations' => [],
            ],
        ],
    ]);

    $rows = app(DefendantDetailReportService::class)
        ->buildRows(Hearing::query()->where('case_no', 'R-MATTER-DECISIONS-001'));

    expect($rows)->toHaveCount(2)
        ->and(collect($rows)->pluck('decided_matter')->all())->toBe(['ЭХТА MD-1', 'ЭХТА MD-2'])
        ->and(collect($rows)->pluck('incoming_matter')->unique()->values()->all())->toBe(['ЭХТА MD-1, ЭХТА MD-2'])
        ->and($rows[0]['decision_status'])->toBe('Ял оногдуулсан')
        ->and($rows[1]['decision_status'])->toBe('Хэрэгсэхгүй болгох');
});

it('shows defendant rowspan with one decision row per decided matter on punishment tab', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА UI-ROW-1', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА UI-ROW-2', 'sort_order' => 2]);
    $m3 = MatterCategory::query()->create(['name' => 'ЭХТА UI-ROW-3', 'sort_order' => 3]);
    $m4 = MatterCategory::query()->create(['name' => 'ЭХХШТХ UI-ROW-4', 'sort_order' => 4]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-UI-MATTER-ROWS-001',
        'title' => 'UI matter rows',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'matter_category_ids' => [$m1->id, $m2->id, $m3->id, $m4->id],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Туршилтын шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id, $m3->id, $m4->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m3->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m4->id, 'decision_type' => 'sentence'],
                ],
                'allocations' => [
                    ['matter_category_id' => $m1->id, 'punishments' => ['fine' => ['fine_units' => 10]]],
                    ['matter_category_id' => $m2->id, 'punishments' => ['fine' => ['fine_units' => 20]]],
                    ['matter_category_id' => $m3->id, 'punishments' => ['fine' => ['fine_units' => 30]]],
                    ['matter_category_id' => $m4->id, 'punishments' => ['fine' => ['fine_units' => 40]]],
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
        ->assertSee('rowspan="4"', false)
        ->assertSeeInOrder([
            'Туршилтын шүүгдэгч',
            'ЭХТА UI-ROW-1',
            'ЭХТА UI-ROW-2',
            'ЭХТА UI-ROW-3',
            'ЭХХШТХ UI-ROW-4',
        ])
        ->assertSee('10')
        ->assertSee('40');
});

it('builds nested preview groups for one hearing with multiple defendants and matters', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА NEST-A', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА NEST-B', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-NEST-GROUP-001',
        'title' => 'Nested group',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'Z',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч А',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => [],
                'allocations' => [
                    ['matter_category_id' => $m1->id, 'punishments' => ['fine' => ['fine_units' => 10]]],
                    ['matter_category_id' => $m2->id, 'punishments' => ['fine' => ['fine_units' => 20]]],
                ],
            ],
            [
                'defendant_name' => 'Шүүгдэгч Б',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => ['fine' => ['fine_units' => 99]],
                'allocations' => [],
            ],
        ],
    ]);

    $service = app(DefendantDetailReportService::class);
    $rows = $service->buildRows(Hearing::query()->where('case_no', 'R-NEST-GROUP-001'));
    $nested = $service->buildNestedPreviewGroups($rows);

    expect($nested)->toHaveCount(1)
        ->and($nested[0]['rowspan_case'])->toBe(3)
        ->and($nested[0]['defendants'])->toHaveCount(2)
        ->and($nested[0]['defendants'][0]['rowspan'])->toBe(2)
        ->and($nested[0]['defendants'][1]['rowspan'])->toBe(1);
});

it('annotates rowspan meta for full ui table on consecutive same defendant and same matter', function () {
    $service = app(DefendantDetailReportService::class);
    $rows = [
        ['hearing_id' => 10, 'defendant_name' => 'А', 'decided_matter' => 'Зүйл 1', 'fine_units' => '1'],
        ['hearing_id' => 10, 'defendant_name' => 'А', 'decided_matter' => 'Зүйл 2', 'fine_units' => '2'],
        ['hearing_id' => 10, 'defendant_name' => 'Б', 'decided_matter' => 'Зүйл 1', 'fine_units' => '3'],
        ['hearing_id' => 10, 'defendant_name' => 'Б', 'decided_matter' => 'Зүйл 1', 'fine_units' => '4'],
    ];
    $ann = $service->annotateRowspanForUiTable($rows);

    expect($ann[0]['_ui_defendant_rowspan'])->toBe(2)
        ->and($ann[0]['_ui_defendant_show'])->toBeTrue()
        ->and($ann[1]['_ui_defendant_show'])->toBeFalse()
        ->and($ann[1]['_ui_decided_matter_show'])->toBeTrue()
        ->and($ann[2]['_ui_defendant_show'])->toBeTrue()
        ->and($ann[2]['_ui_decided_matter_rowspan'])->toBe(2)
        ->and($ann[2]['_ui_decided_matter_show'])->toBeTrue()
        ->and($ann[3]['_ui_decided_matter_show'])->toBeFalse();
});

it('renders nested detail table cells aligned with column headers', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА ALIGN-1', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА ALIGN-2', 'sort_order' => 2]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-COL-ALIGN-001',
        'title' => 'Column align',
        'hearing_state' => 'Дууссан',
        'hearing_date' => now()->toDateString(),
        'hour' => 9,
        'minute' => 30,
        'courtroom' => '101',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч А',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => [],
                'allocations' => [
                    ['matter_category_id' => $m1->id, 'punishments' => ['fine' => ['fine_units' => 11]]],
                    ['matter_category_id' => $m2->id, 'punishments' => ['fine' => ['fine_units' => 22]]],
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
        ->assertSeeInOrder([
            'R-COL-ALIGN-001',
            'Дууссан',
            'Шүүгдэгч А',
            'ЭХТА ALIGN-1',
            'ЭХТА ALIGN-2',
        ]);
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
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй файл')
        ->assertDontSee('Excel татах');
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
        ->assertSee('Шүүгдэгчийн дэлгэрэнгүй тайлан');
});

it('fills defendant age and gender in detail report from hearing registries per defendant rowspan', function () {
    ensureReportRole('admin');
    ensureReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА DEMO-1', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА DEMO-2', 'sort_order' => 2]);
    $m3 = MatterCategory::query()->create(['name' => 'ЭХТА DEMO-3', 'sort_order' => 3]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'R-DEMO-ROWSPAN-001',
        'title' => 'Demographics rowspan',
        'hearing_date' => '2026-05-18',
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'defendant_names' => ['Эмэгтэй шүүгдэгч', 'Эрэгтэй шүүгдэгч'],
        'defendant_registries' => ['ЙС96072608', 'ЙС96072619'],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Эмэгтэй шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id, $m3->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m3->id, 'decision_type' => 'sentence'],
                ],
                'allocations' => [
                    ['matter_category_id' => $m1->id, 'punishments' => ['fine' => ['fine_units' => 10]]],
                    ['matter_category_id' => $m2->id, 'punishments' => ['fine' => ['fine_units' => 20]]],
                    ['matter_category_id' => $m3->id, 'punishments' => ['fine' => ['fine_units' => 30]]],
                ],
            ],
            [
                'defendant_name' => 'Эрэгтэй шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => ['fine' => ['fine_units' => 99]],
            ],
        ],
    ]);

    $rows = app(DefendantDetailReportService::class)
        ->buildRows(Hearing::query()->where('case_no', 'R-DEMO-ROWSPAN-001'));

    expect(collect($rows)->pluck('defendant_age')->filter()->unique()->count())->toBeGreaterThanOrEqual(1)
        ->and(collect($rows)->pluck('defendant_gender')->filter()->unique()->count())->toBe(2)
        ->and(collect($rows)->firstWhere('defendant_name', 'Эмэгтэй шүүгдэгч')['defendant_gender'])->toBe('Эмэгтэй')
        ->and(collect($rows)->firstWhere('defendant_name', 'Эрэгтэй шүүгдэгч')['defendant_gender'])->toBe('Эрэгтэй');

    actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'punishment',
        ]))
        ->assertOk()
        ->assertSee('rowspan="3"', false)
        ->assertSee('Эмэгтэй')
        ->assertSee('Эрэгтэй');
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
