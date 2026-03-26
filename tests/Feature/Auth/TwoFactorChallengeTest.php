<?php

use Laravel\Fortify\Features;

test('two factor challenge redirects to login when not authenticated', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    $this->markTestSkipped('Нэвтрэхийг Auth\\LoginController ашигладаг тул Fortify-ийн 2FA challenge автоматаар ажиллахгүй.');
});
