<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Services\Reports\ArticleCategoryMetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureArticleReportRole(string $name): void
{
    Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

it('aggregates detailed article metrics per matter category', function () {
    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-2.3', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 11.6-2', 'sort_order' => 2]);

    $hearing = Hearing::query()->create([
        'title' => 'Article metrics',
        'case_no' => 'ART-METRICS-001',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч А',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id, $m2->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                    ['matter_category_id' => $m2->id, 'decision_type' => 'dismiss'],
                ],
                'allocations' => [
                    [
                        'matter_category_id' => $m1->id,
                        'punishments' => [
                            'fine' => ['fine_units' => 100, 'damage_amount' => 5000],
                            'community_service' => ['hours' => 40],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
    $rows = (new ArticleCategoryMetricsAggregator)->buildRows([$hearing], $matterMap);

    $byName = collect($rows)->keyBy('name');

    expect($byName->has('ЭХТА 10.1-2.3'))->toBeTrue()
        ->and($byName->has('ЭХТА 11.6-2'))->toBeTrue()
        ->and($byName['ЭХТА 10.1-2.3']['fine_units'])->toBe(100)
        ->and($byName['ЭХТА 10.1-2.3']['community_service_hours'])->toBe(40)
        ->and($byName['ЭХТА 10.1-2.3']['damage_amount'])->toBe(5000)
        ->and($byName['ЭХТА 11.6-2']['dismiss_count'])->toBe(1);
});

it('shows detailed article columns on admin article report tab', function () {
    ensureArticleReportRole('admin');
    ensureArticleReportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА REPORT-COL', 'sort_order' => 1]);

    Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'ART-UI-001',
        'title' => 'UI article report',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'notes_defendant_sentences' => [
            [
                'defendant_name' => 'Шүүгдэгч',
                'outcome_track' => 'sentence',
                'decided_matter_ids' => [$m1->id],
                'matter_decisions' => [
                    ['matter_category_id' => $m1->id, 'decision_type' => 'sentence'],
                ],
                'punishments' => [
                    'fine' => ['fine_units' => 1000],
                ],
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'tab' => 'article',
        ]))
        ->assertOk()
        ->assertSee('Цагаатгах')
        ->assertSee('Торгох (нэгж)')
        ->assertSee('Эд мөрийн баримт устгуулах')
        ->assertSee('ЭХТА REPORT-COL');
});
