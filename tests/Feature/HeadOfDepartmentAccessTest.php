<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureHeadRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('allows head of department to view admin hearings and users index', function () {
    ensureHeadRole('head_of_department');

    $head = User::factory()->create();
    $head->assignRole('head_of_department');

    $this->actingAs($head)
        ->get(route('admin.hearings.index'))
        ->assertOk()
        ->assertDontSee('Хурлын зар оруулах')
        ->assertDontSee('Засварлах')
        ->assertDontSee('Үйлдэл');

    $this->actingAs($head)
        ->get(route('admin.users.index'))
        ->assertOk();
});

it('forbids head of department from admin-only management routes', function () {
    ensureHeadRole('admin');
    ensureHeadRole('head_of_department');

    $head = User::factory()->create();
    $head->assignRole('head_of_department');

    $this->actingAs($head)
        ->get(route('admin.hearings.create'))
        ->assertForbidden();

    $this->actingAs($head)
        ->get(route('admin.hearings.print'))
        ->assertForbidden();

    $this->actingAs($head)
        ->post(route('admin.users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'role' => 'judge',
        ])
        ->assertForbidden();
});
