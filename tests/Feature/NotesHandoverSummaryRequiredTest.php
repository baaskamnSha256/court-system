<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function ensureNotesSummaryRole(string $name): void
{
    Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

it('requires hearing summary for admin notes handover update', function () {
    ensureNotesSummaryRole('admin');
    ensureNotesSummaryRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'SUMMARY-REQ-ADMIN',
        'title' => 'Summary required',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
        'notes_decision_status' => 'Хойшилсон',
    ]);

    actingAs($admin)
        ->from(route('admin.notes.index'))
        ->patch(route('admin.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Хойшилсон',
            'notes_handover_text' => '   ',
        ])
        ->assertSessionHasErrors('notes_handover_text');
});

it('requires hearing summary for secretary notes handover update', function () {
    ensureNotesSummaryRole('secretary');
    ensureNotesSummaryRole('court_clerk');

    $secretary = User::factory()->create();
    $secretary->assignRole('secretary');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $hearing = Hearing::query()->create([
        'created_by' => $secretary->id,
        'case_no' => 'SUMMARY-REQ-SEC',
        'title' => 'Summary required secretary',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
        'notes_decision_status' => 'Хойшилсон',
    ]);

    actingAs($secretary)
        ->from(route('secretary.notes.index'))
        ->patch(route('secretary.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Хойшилсон',
            'notes_handover_text' => '',
        ])
        ->assertSessionHasErrors('notes_handover_text');
});

it('requires hearing summary for head of department notes handover update', function () {
    ensureNotesSummaryRole('admin');
    ensureNotesSummaryRole('head_of_department');
    ensureNotesSummaryRole('court_clerk');

    $head = User::factory()->create();
    $head->assignRole('head_of_department');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $hearing = Hearing::query()->create([
        'created_by' => $head->id,
        'case_no' => 'SUMMARY-REQ-HEAD',
        'title' => 'Summary required head',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
        'notes_decision_status' => 'Хойшилсон',
    ]);

    actingAs($head)
        ->from(route('admin.notes.index'))
        ->patch(route('admin.notes.update', $hearing), [
            'clerk_id' => $clerk->id,
            'notes_decision_status' => 'Хойшилсон',
            'notes_handover_text' => '',
        ])
        ->assertSessionHasErrors('notes_handover_text');
});

it('embeds summary requirement flag for admin and secretary notes handover modals', function () {
    ensureNotesSummaryRole('admin');
    ensureNotesSummaryRole('secretary');
    ensureNotesSummaryRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $secretary = User::factory()->create();
    $secretary->assignRole('secretary');
    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $hearing = Hearing::query()->create([
        'created_by' => $admin->id,
        'case_no' => 'SUMMARY-REQ-UI',
        'title' => 'Summary UI',
        'hearing_date' => now()->toDateString(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'clerk_id' => $clerk->id,
    ]);

    $adminHtml = actingAs($admin)
        ->get(route('admin.notes.index', ['hearing_date' => $hearing->hearing_date]))
        ->assertSuccessful()
        ->getContent();

    expect($adminHtml)->toContain('submitNotesHandover')
        ->and($adminHtml)->toMatch('/requireNotesSummary.{0,12}true/s');

    $hearing->update(['created_by' => $secretary->id]);

    $secretaryHtml = actingAs($secretary)
        ->get(route('secretary.notes.index', ['hearing_date' => $hearing->hearing_date]))
        ->assertSuccessful()
        ->getContent();

    expect($secretaryHtml)->toContain('submitNotesHandover')
        ->and($secretaryHtml)->toMatch('/requireNotesSummary.{0,12}true/s');
});
