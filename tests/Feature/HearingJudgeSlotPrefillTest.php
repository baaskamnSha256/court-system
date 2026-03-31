<?php

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function hearingJudgePrefillRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('orders judge slot ids from hearing_judges.position', function () {
    hearingJudgePrefillRole('judge');

    $j1 = User::factory()->create(['name' => 'Slot Пред']);
    $j1->assignRole('judge');
    $j2 = User::factory()->create(['name' => 'Slot Гишүүн1']);
    $j2->assignRole('judge');
    $j3 = User::factory()->create(['name' => 'Slot Гишүүн2']);
    $j3->assignRole('judge');

    $hearing = Hearing::query()->create([
        'title' => 'Prefill',
        'case_no' => '2026/SLOT-001',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $j1->id,
    ]);

    $hearing->judges()->sync([
        $j2->id => ['position' => 1],
        $j3->id => ['position' => 2],
        $j1->id => ['position' => 3],
    ]);

    expect($hearing->fresh()->judgeSlotUserIdsOrdered())->toBe([$j2->id, $j3->id, $j1->id]);
});

it('falls back to judge_names_text when pivot is empty', function () {
    hearingJudgePrefillRole('judge');

    $presiding = User::factory()->create(['name' => 'Нэр Дарга']);
    $presiding->assignRole('judge');
    $member = User::factory()->create(['name' => 'Нэр Гишүүн']);
    $member->assignRole('judge');

    $hearing = Hearing::query()->create([
        'title' => 'Text judges',
        'case_no' => '2026/SLOT-002',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $presiding->id,
        'judge_names_text' => 'Нэр Дарга, Нэр Гишүүн',
    ]);

    expect($hearing->judgeSlotUserIdsOrdered())->toBe([$presiding->id, $member->id]);
});

it('resolves judge ids from text with partial fallback', function () {
    hearingJudgePrefillRole('judge');

    $judge = User::factory()->create(['name' => 'Б.Бат-Эрдэнэ']);
    $judge->assignRole('judge');

    $hearing = Hearing::query()->create([
        'title' => 'Text partial',
        'case_no' => '2026/SLOT-003',
        'start_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $judge->id,
        'judge_names_text' => 'Бат-Эрдэнэ',
    ]);

    expect($hearing->judgeSlotUserIdsOrdered())->toBe([$judge->id]);
});
