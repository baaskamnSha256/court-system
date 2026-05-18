<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows court clerk to update decision status and defendant sentences from notes handover', function () {
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'court_clerk', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $matter = MatterCategory::query()->create(['name' => 'ЭХТА CLERK-1', 'sort_order' => 1]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'CLERK-NOTES-001',
        'title' => 'Clerk notes',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
        'defendant_names' => ['Шүүгдэгч А'],
        'matter_category_ids' => [$matter->id],
        'notes_decision_status' => 'Хойшилсон',
        'notes_handover_text' => 'Хуучин тойм',
    ]);

    actingAs($clerk)
        ->patch(route('court_clerk.notes.update', $hearing), [
            'notes_decision_status' => 'Шийдвэрлэсэн',
            'notes_handover_text' => 'Шинэчилсэн тойм',
            'notes_handover_issued' => 1,
            'notes_defendant_sentences' => [
                [
                    'defendant_name' => 'Шүүгдэгч А',
                    'decided_matter_ids' => [$matter->id],
                    'outcome_track' => 'sentence',
                    'matter_decisions' => [
                        [
                            'matter_category_id' => $matter->id,
                            'decision_type' => 'sentence',
                        ],
                    ],
                    'punishments' => [
                        'fine' => [
                            'enabled' => 1,
                            'fine_units' => '500',
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $hearing->refresh();

    expect($hearing->notes_decision_status)->toBe('Шийдвэрлэсэн')
        ->and($hearing->notes_handover_text)->toBe('Шинэчилсэн тойм')
        ->and($hearing->notes_handover_issued)->toBeTrue()
        ->and($hearing->notes_defendant_sentences)->toBeArray()
        ->and($hearing->notes_defendant_sentences[0]['defendant_name'])->toBe('Шүүгдэгч А');
});

it('renders full notes handover modal controls for court clerk', function () {
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'court_clerk', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'CLERK-NOTES-UI-001',
        'title' => 'Clerk modal',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
        'defendant_names' => ['Шүүгдэгч А'],
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $html = actingAs($clerk)
        ->get(route('court_clerk.notes.index', ['hearing_date' => $hearing->hearing_date]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Тэмдэглэл засварлах')
        ->and($html)->toContain('x-model="decisionStatus"')
        ->and($html)->toContain('Шүүгдэгч тус бүрийн шийтгэлийн мэдээлэл')
        ->and($html)->toContain('name="notes_handover_issued"')
        ->and($html)->not->toContain('name="notes_decision_status" disabled');
});
