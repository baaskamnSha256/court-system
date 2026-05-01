<?php

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('shows notification logs list for admin users', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    NotificationLog::query()->create([
        'hearing_id' => 12,
        'action' => 'updated',
        'recipient_role' => 'victim',
        'recipient_name' => 'Бат',
        'regnum' => 'AB12345678',
        'title' => 'Шүүх хуралдааны зар шинэчлэгдлээ',
        'delivery_status' => 'delivered',
        'delivered' => true,
        'api_status' => 200,
        'api_message' => 'Амжилттай',
        'request_id' => 'req-1',
        'sent_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notifications.logs.index'))
        ->assertSuccessful()
        ->assertSee('Мэдэгдлийн лог')
        ->assertSee('victim')
        ->assertSee('Бат')
        ->assertSee('AB12345678')
        ->assertSee('delivered');
});
