<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('shows all hearings for secretary without action column', function () {
    Role::query()->firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);

    $secretary = User::factory()->create();
    $otherUser = User::factory()->create();
    $secretary->assignRole('secretary');

    Hearing::query()->create([
        'title' => 'Own hearing',
        'case_no' => 'SEC-OWN-001',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $secretary->id,
    ]);

    Hearing::query()->create([
        'title' => 'Other hearing',
        'case_no' => 'SEC-OTHER-001',
        'start_at' => now()->addDays(2),
        'status' => 'scheduled',
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($secretary)
        ->get(route('secretary.hearings.index'))
        ->assertOk()
        ->assertSee('Хурлын зарууд')
        ->assertSee('SEC-OWN-001')
        ->assertSee('SEC-OTHER-001')
        ->assertSee('Хурлын төлөвөөр зарлагдсан тоо')
        ->assertDontSee('Хурлын зар (Шүүгчийн туслах)')
        ->assertDontSee('title="Засварлах"', false)
        ->assertDontSee('title="Устгах"', false);
});
