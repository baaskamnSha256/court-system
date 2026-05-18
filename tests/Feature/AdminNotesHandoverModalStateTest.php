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

it('keeps each defendant sentencing pane as a direct child of the modal pane stack', function () {
    seedNotesHandoverRoles();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $matter = MatterCategory::query()->create(['name' => 'Cat pane stack', 'sort_order' => 1]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'NOTES-PANE-STACK-001',
        'title' => 'Pane stack',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'B',
        'matter_category_ids' => [$matter->id],
        'defendant_names' => ['First Def', 'Second Def', 'Third Def'],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'clerk_id' => $clerk->id,
        'notes_handover_issued' => false,
    ]);

    $html = actingAs($admin)
        ->get(route('admin.notes.index', [
            'hearing_date' => $hearing->hearing_date,
            'notes_decision_status' => 'Шийдвэрлэсэн',
        ]))
        ->assertSuccessful()
        ->getContent();

    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $stacks = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' space-y-3 ')]");
    expect($stacks->length)->toBeGreaterThan(0);

    $stack = $stacks->item(0);
    $directPaneCount = 0;
    foreach ($stack->childNodes as $child) {
        if (! $child instanceof DOMElement || $child->tagName !== 'div') {
            continue;
        }
        $xShow = $child->getAttribute('x-show');
        if ($xShow !== '' && str_contains($xShow, 'selectedDefendantId')) {
            $directPaneCount++;
        }
    }

    expect($directPaneCount)->toBe(3);
});

it('uses distinct Alpine x-for keys per defendant for matter lists', function () {
    seedNotesHandoverRoles();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $matter = MatterCategory::query()->create(['name' => 'Cat keys', 'sort_order' => 1]);

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'NOTES-MATTER-KEYS-001',
        'title' => 'Matter keys',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'B',
        'matter_category_ids' => [$matter->id],
        'defendant_names' => ['Alpha', 'Beta', 'Gamma'],
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'clerk_id' => $clerk->id,
        'notes_handover_issued' => false,
    ]);

    $html = actingAs($admin)
        ->get(route('admin.notes.index', [
            'hearing_date' => $hearing->hearing_date,
            'notes_decision_status' => 'Шийдвэрлэсэн',
        ]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain("'d0-matter-chip-'+matterId")
        ->and($html)->toContain("'d1-matter-chip-'+matterId")
        ->and($html)->toContain("'d2-matter-chip-'+matterId");
});
