<?php

use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Support\HearingSummaryDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('formats hearing summary row for report tables', function () {
    $category = MatterCategory::query()->create(['name' => 'ЭХ Зүйл', 'sort_order' => 1]);
    $prosecutor = User::factory()->create(['name' => 'Яллагч А']);

    $hearing = Hearing::query()->create([
        'title' => 'Summary row hearing',
        'case_no' => '2026/SUM/001',
        'hearing_date' => '2026-05-10',
        'hour' => 9,
        'minute' => 30,
        'courtroom' => '301',
        'prosecutor_id' => $prosecutor->id,
        'prosecutor_name' => $prosecutor->name,
        'defendants' => 'Шүүгдэгч Нэг',
        'matter_category_ids' => [$category->id],
        'defendant_lawyers_text' => ['Өмгөөлөгч Б'],
        'preventive_measure' => 'Хорих',
        'victim_name' => 'Хохирогч Г',
        'witnesses' => 'Гэрч Д',
    ]);

    $hearing->judges()->attach(User::factory()->create(['name' => 'Шүүгч С'])->id, ['position' => 1]);
    $hearing->load('judges', 'prosecutor');

    $row = HearingSummaryDisplay::row($hearing, MatterCategory::query()->pluck('name', 'id'));

    expect($row['case_no'])->toBe('2026/SUM/001')
        ->and($row['courtroom'])->toBe('301')
        ->and($row['judges'])->toContain('Шүүгч С')
        ->and($row['prosecutor'])->toBe('Яллагч А')
        ->and($row['defendants'])->toBe('Шүүгдэгч Нэг')
        ->and($row['matter_categories'])->toBe(['ЭХ Зүйл'])
        ->and($row['lawyers'])->toContain('ШүӨм: Өмгөөлөгч Б')
        ->and($row['preventive'])->toContain('Хорих')
        ->and($row['other_parties'])->toContain('Хохирогч: Хохирогч Г')
        ->and($row['other_parties'])->toContain('Гэрч: Гэрч Д')
        ->and($row['decision_status'])->toBe('Хүлээгдэж буй');
});

it('includes decision summary fields in report row data', function () {
    Hearing::query()->create([
        'title' => 'Decision fields hearing',
        'case_no' => '2026/DEC-FIELDS',
        'hearing_date' => '2026-05-12',
        'hour' => 14,
        'minute' => 0,
        'courtroom' => 'B-1',
        'defendants' => 'Шүүгдэгч',
        'notes_handover_text' => 'Хойшилсон тойм текст',
        'notes_decided_matter' => 'Зүйл А, Зүйл Б',
        'notes_decision_status' => 'Хойшилсон',
    ]);

    $hearing = Hearing::query()->where('case_no', '2026/DEC-FIELDS')->firstOrFail();
    $row = HearingSummaryDisplay::row($hearing, collect());

    expect($row['decision_summary'])->toBe('Хойшилсон тойм текст')
        ->and($row['decided_matter_lines'])->toBe(['Зүйл А', 'Зүйл Б'])
        ->and($row['decision_status'])->toBe('Хойшилсон');
});
