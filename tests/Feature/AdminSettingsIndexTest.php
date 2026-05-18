<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('shows settings links without a duplicate reports card', function () {
    Role::query()->firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Зүйл анги')
        ->assertSee('Хэрэглэгч удирдах')
        ->assertDontSee('Тайлангийн төрлүүд рүү орж статистик харах', false);
});
