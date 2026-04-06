<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates head of department role on demand when creating a user', function () {
    Role::query()->firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
    Role::query()->where('name', 'head_of_department')->delete();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Head User',
            'email' => 'head-role@court.mn',
            'password' => 'password',
            'role' => 'head_of_department',
            'is_active' => '1',
        ])
        ->assertRedirect();

    $createdUser = User::query()->where('email', 'head-role@court.mn')->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser?->hasRole('head_of_department'))->toBeTrue()
        ->and(Role::query()->where('name', 'head_of_department')->where('guard_name', 'web')->exists())->toBeTrue();
});
