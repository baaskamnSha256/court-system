<?php

use App\Models\Hearing;
use App\Models\User;
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

it('includes pivot-assigned judges on admin edit even when they no longer have the judge role', function () {
    ensureRole('admin');
    ensureRole('judge');
    ensureRole('prosecutor');
    ensureRole('lawyer');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $judge = User::factory()->create(['name' => 'PivotJudge NoRole']);
    $judge->assignRole('judge');

    $hearing = Hearing::query()->create([
        'title' => 'Edit judges test',
        'case_no' => '2026/EDIT-JUDGES-001',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $admin->id,
    ]);

    $hearing->judges()->attach($judge->id, ['position' => 1]);
    $judge->removeRole('judge');

    $this->actingAs($admin)
        ->get(route('admin.hearings.edit', $hearing))
        ->assertOk()
        ->assertSee('PivotJudge NoRole', false);
});
