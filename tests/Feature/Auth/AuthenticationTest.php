<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

function authTestEnsureRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Шүүх Хуралдааны зар товлох дотоод удирдлагын систем')
        ->assertSee('data-password-toggle', false);
});

test('users can authenticate using the login screen', function () {
    authTestEnsureRole('admin');

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('head of department users are redirected to admin dashboard', function () {
    authTestEnsureRole('head_of_department');

    $user = User::factory()->create();
    $user->assignRole('head_of_department');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('remember me queues persistent login cookie', function () {
    authTestEnsureRole('admin');

    $user = User::factory()->create();
    $user->assignRole('admin');

    $recaller = Auth::guard('web')->getRecallerName();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])
        ->assertSessionHasNoErrors()
        ->assertCookieNotExpired($recaller);

    $this->assertAuthenticatedAs($user);
});

test('remember me is omitted when checkbox not sent', function () {
    authTestEnsureRole('admin');

    $user = User::factory()->create();
    $user->assignRole('admin');

    $recaller = Auth::guard('web')->getRecallerName();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertCookieMissing($recaller);
});

test('users can authenticate when email casing differs from stored value', function () {
    authTestEnsureRole('admin');

    $user = User::factory()->create([
        'email' => 'AdminUser@Example.COM',
    ]);
    $user->assignRole('admin');

    $response = $this->post(route('login.store'), [
        'email' => 'adminuser@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users can authenticate with eight digit phone when stored on profile', function () {
    authTestEnsureRole('judge');

    $user = User::factory()->create([
        'phone' => '88112233',
        'email' => 'phone-user@example.com',
    ]);
    $user->assignRole('judge');

    $response = $this->post(route('login.store'), [
        'email' => '88112233',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('judge.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('inactive users are rejected after password check', function () {
    authTestEnsureRole('admin');

    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('admin');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users without any spatie role cannot authenticate', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->markTestSkipped('Нэвтрэхийг Auth\\LoginController ашигладаг тул Fortify-ийн 2FA challenge автоматаар ажиллахгүй.');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
