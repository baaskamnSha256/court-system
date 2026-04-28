<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function seedNotesHandoverRoles(): void
{
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'court_clerk', 'guard_name' => 'web']);
}

it('embeds saved notes handover values in the admin row alpine config', function () {
    seedNotesHandoverRoles();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $matter = MatterCategory::query()->create(['name' => 'Cat 1', 'sort_order' => 1]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'NOTES-MODAL-STATE-001',
        'title' => 'Modal state',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'B',
        'matter_category_ids' => [$matter->id],
        'notes_handover_text' => 'Unique modal summary ASCII',
        'notes_decision_status' => 'Хойшилсон',
        'clerk_id' => $clerk->id,
        'notes_handover_issued' => true,
    ]);

    $html = actingAs($admin)
        ->get(route('admin.notes.index', ['hearing_date' => $hearing->hearing_date]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('notesHandoverRow')
        ->and($html)->toContain('notes-form-'.$hearing->id)
        ->and($html)->toContain('Unique modal summary ASCII')
        ->and($html)->toMatch('/savedClerkId.{0,40}'.preg_quote((string) $clerk->id, '/').'/s')
        ->and($html)->toMatch('/savedNotesHandoverIssued.{0,12}true/s');
});
