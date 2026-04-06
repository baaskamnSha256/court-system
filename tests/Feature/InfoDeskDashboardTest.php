<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('shows info desk dashboard without role hearings widgets', function () {
    Role::query()->firstOrCreate([
        'name' => 'info_desk',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole('info_desk');

    $this->actingAs($user)
        ->get(route('info_desk.dashboard'))
        ->assertOk()
        ->assertDontSee('Шүүх хуралдааны шийдвэр (жилээр)')
        ->assertDontSee('Календарь');
});
