<?php

use App\Models\ActivityLog;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('shows activity logs for admin', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'hearing.updated',
        'subject_type' => null,
        'subject_id' => null,
        'description' => 'Хурлын зар шинэчиллээ — хэрэг № TEST-001',
        'properties' => ['case_no' => 'TEST-001', 'hearing_id' => 1],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'))
        ->assertSuccessful()
        ->assertSee('Үйлдлийн түүх')
        ->assertSee('Нэг хуудсанд хамгийн ихдээ 50 бичлэг')
        ->assertSee('hearing.updated')
        ->assertSee('TEST-001')
        ->assertSee($admin->name);
});

it('shows file download description in тайлбар column only', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'file.download',
        'description' => 'Файл татсан хурлын зар',
        'properties' => [
            'download_label' => 'хурлын зар',
            'download_filename' => 'hearings-2026-05-04.xlsx',
            'route' => 'admin.hearings.print.download',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'))
        ->assertSuccessful()
        ->assertSee('Файл татсан хурлын зар');
});

it('filters activity logs by case number in properties', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'hearing.updated',
        'description' => 'А',
        'properties' => ['case_no' => 'UNIQUE-CASE-XYZ'],
    ]);

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'hearing.updated',
        'description' => 'Б',
        'properties' => ['case_no' => 'OTHER'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index', ['case_no' => 'UNIQUE-CASE']))
        ->assertSuccessful()
        ->assertSee('UNIQUE-CASE-XYZ')
        ->assertDontSee('OTHER');
});

it('filters by category auth', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'auth.login',
        'description' => 'Системд нэвтэрлээ',
        'properties' => ['roles' => ['admin']],
    ]);

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'http.post',
        'description' => 'POST хүсэлт',
        'properties' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index', ['category' => 'auth']))
        ->assertSuccessful()
        ->assertSee('auth.login')
        ->assertDontSee('POST хүсэлт');
});

it('filters activity logs by user field', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $other = User::factory()->create(['name' => 'Other Person', 'email' => 'other-unique@example.test']);

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'auth.login',
        'description' => 'Log for admin user row',
        'properties' => null,
    ]);

    ActivityLog::query()->create([
        'user_id' => $other->id,
        'action' => 'auth.login',
        'description' => 'Log for other user row',
        'properties' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index', ['user' => 'other-unique@']))
        ->assertSuccessful()
        ->assertSee('Log for other user row')
        ->assertDontSee('Log for admin user row');
});

it('forbids secretary from viewing admin activity logs', function () {
    Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);

    $secretary = User::factory()->create();
    $secretary->assignRole('secretary');

    $this->actingAs($secretary)
        ->get(route('admin.activity-logs.index'))
        ->assertForbidden();
});
