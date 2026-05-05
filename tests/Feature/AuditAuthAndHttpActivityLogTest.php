<?php

use App\Models\ActivityLog;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('records auth.login when user signs in', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $user->id,
        'action' => 'auth.login',
    ]);
});

it('records auth.logout when user signs out', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/login');

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $user->id,
        'action' => 'auth.logout',
    ]);
});

it('records http.post for authenticated mutating requests', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $name = 'AuditCat-'.uniqid('', true);

    $this->actingAs($admin)
        ->post(route('admin.matter-categories.store'), ['name' => $name])
        ->assertRedirect();

    expect(ActivityLog::query()->where('action', 'http.post')->where('user_id', $admin->id)->exists())->toBeTrue();
});
