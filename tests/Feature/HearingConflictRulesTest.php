<?php

use App\Http\Controllers\Concerns\ManagesHearingLogic;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureRoleForConflictTest(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

function hearingLogicProbe(): object
{
    return new class
    {
        use ManagesHearingLogic;

        public function duration(array $judgeIds): int
        {
            return $this->computeDurationMinutes($judgeIds);
        }

        public function window(string $date, int $hour, int $minute, int $durationMinutes): array
        {
            return $this->buildStartEnd($date, $hour, $minute, $durationMinutes);
        }

        public function assertConflict(
            \Carbon\Carbon $start,
            \Carbon\Carbon $end,
            string $courtroom,
            array $judgeIds = [],
            array $lawyerNames = [],
            ?int $ignoreHearingId = null,
            array $prosecutorIds = []
        ): void {
            $this->assertNoConflict($start, $end, $courtroom, $judgeIds, $lawyerNames, $ignoreHearingId, $prosecutorIds);
        }
    };
}

it('uses 10-minute default and 30-minute panel durations', function () {
    $probe = hearingLogicProbe();

    expect($probe->duration([1]))->toBe(10);
    expect($probe->duration([1, 2, 3]))->toBe(30);
});

it('treats exactly 10 minutes as non-overlapping in courtroom conflict check', function () {
    $probe = hearingLogicProbe();
    $date = now()->toDateString();

    Hearing::query()->create([
        'title' => 'Existing hearing',
        'case_no' => 'CF-001',
        'status' => 'scheduled',
        'courtroom' => 'A',
        'start_at' => now()->setTime(10, 0),
        'end_at' => now()->setTime(10, 10),
    ]);

    [$start, $end] = $probe->window($date, 10, 10, 10);
    $probe->assertConflict($start, $end, 'A');

    [$conflictStart, $conflictEnd] = $probe->window($date, 10, 9, 10);
    expect(function () use ($probe, $conflictStart, $conflictEnd) {
        $probe->assertConflict($conflictStart, $conflictEnd, 'A');
    })->toThrow(ValidationException::class);
});

it('checks lawyer overlap in court clerk conflict endpoint', function () {
    ensureRoleForConflictTest('court_clerk');
    ensureRoleForConflictTest('judge');

    $clerk = User::factory()->create();
    $clerk->assignRole('court_clerk');

    $judgeOne = User::factory()->create();
    $judgeTwo = User::factory()->create();
    $judgeOne->assignRole('judge');
    $judgeTwo->assignRole('judge');

    Hearing::query()->create([
        'title' => 'Existing lawyer hearing',
        'case_no' => 'CF-LAW-001',
        'status' => 'scheduled',
        'courtroom' => 'A',
        'start_at' => now()->setTime(11, 0),
        'end_at' => now()->setTime(11, 10),
        'defendant_lawyers_text' => ['Бат Өмгөөлөгч'],
    ]);

    $response = $this->actingAs($clerk)->postJson(route('court_clerk.hearings.checkConflict'), [
        'hearing_date' => now()->toDateString(),
        'hour' => 11,
        'minute' => 0,
        'courtroom' => 'Б',
        'presiding_judge_id' => $judgeOne->id,
        'member_judge_1_id' => null,
        'member_judge_2_id' => null,
        'ignore_id' => null,
        'defendant_lawyers_text' => ['Бат Өмгөөлөгч'],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('field', 'defendant_lawyers_text');
});
