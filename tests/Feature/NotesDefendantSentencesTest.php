<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function ensureNotesRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('saves defendant-level sentences when decision is solved', function () {
    ensureNotesRole('admin');
    ensureNotesRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-2.2', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-2.3', 'sort_order' => 2]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'CASE-001',
        'title' => 'Test hearing',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'matter_category_ids' => [$m1->id, $m2->id],
    ]);

    actingAs($admin)
        ->patch(route('admin.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Шийдвэрлэсэн',
            'notes_handover_text' => 'Шийдвэрлэсэн тойм',
            'notes_handover_issued' => 1,
            'notes_defendant_sentences' => [
                [
                    'defendant_name' => 'Шүүгдэгч 1',
                    'decided_matter_ids' => [$m1->id],
                    'punishments' => [
                        'fine' => [
                            'enabled' => 1,
                            'fine_units' => '1,200,000',
                            'damage_amount' => '300,000',
                        ],
                        'community_service' => [
                            'enabled' => 1,
                            'hours' => 120,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $hearing->refresh();

    expect($hearing->notes_defendant_sentences)->toBeArray()
        ->and($hearing->notes_defendant_sentences[0]['defendant_name'])->toBe('Шүүгдэгч 1')
        ->and($hearing->notes_defendant_sentences[0]['punishments']['fine']['fine_units'])->toBe(1200000)
        ->and($hearing->notes_defendant_sentences[0]['punishments']['community_service']['hours'])->toBe(120)
        ->and($hearing->notes_decided_matter)->toContain('ЭХТА 10.1-2.2');
});

it('saves per-article allocations when provided', function () {
    ensureNotesRole('admin');
    ensureNotesRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 11.6', 'sort_order' => 1]);
    $m2 = MatterCategory::query()->create(['name' => 'ЭХТА 12.1', 'sort_order' => 2]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'CASE-ALLOC-001',
        'title' => 'Test hearing alloc',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'matter_category_ids' => [$m1->id, $m2->id],
    ]);

    actingAs($admin)
        ->patch(route('admin.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Шийдвэрлэсэн',
            'notes_defendant_sentences' => [
                [
                    'defendant_name' => 'Шүүгдэгч C',
                    'decided_matter_ids' => [$m1->id, $m2->id],
                    'punishments' => [
                        'fine' => [
                            'enabled' => 1,
                            'fine_units' => '2,000',
                            'damage_amount' => '500,000',
                        ],
                    ],
                    'allocations' => [
                        [
                            'matter_category_id' => $m1->id,
                            'punishments' => [
                                'fine' => [
                                    'fine_units' => '1200',
                                    'damage_amount' => '300000',
                                ],
                            ],
                        ],
                        [
                            'matter_category_id' => $m2->id,
                            'punishments' => [
                                'fine' => [
                                    'fine_units' => '800',
                                    'damage_amount' => '200000',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $hearing->refresh();
    expect($hearing->notes_defendant_sentences[0]['allocations'])->toHaveCount(2)
        ->and($hearing->notes_defendant_sentences[0]['allocations'][0]['matter_category_id'])->toBe($m1->id)
        ->and($hearing->notes_defendant_sentences[0]['allocations'][0]['punishments']['fine']['fine_units'])->toBe(1200);
});

it('rejects community service hours above 720', function () {
    ensureNotesRole('admin');
    ensureNotesRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');
    $m1 = MatterCategory::query()->create(['name' => 'ЭХТА 10.1-2.2', 'sort_order' => 1]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'CASE-002',
        'title' => 'Test hearing 2',
        'hearing_date' => now()->toDateString(),
        'hour' => 11,
        'minute' => 0,
        'courtroom' => 'A',
    ]);

    actingAs($admin)
        ->from(route('admin.notes.index'))
        ->patch(route('admin.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Шийдвэрлэсэн',
            'notes_defendant_sentences' => [
                [
                    'defendant_name' => 'Шүүгдэгч 2',
                    'decided_matter_ids' => [$m1->id],
                    'punishments' => [
                        'community_service' => [
                            'enabled' => 1,
                            'hours' => 800,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('admin.notes.index'))
        ->assertSessionHasErrors('notes_defendant_sentences');
});
