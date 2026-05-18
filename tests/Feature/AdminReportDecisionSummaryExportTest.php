<?php

use App\Models\Hearing;
use App\Models\User;
use App\Services\Reports\DecisionSummaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ensureDecisionExportRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

it('exports decision summary hearings in the same columns as the on-screen table', function () {
    ensureDecisionExportRole('admin');
    ensureDecisionExportRole('court_clerk');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    Hearing::query()->create([
        'title' => 'Excel export hearing',
        'case_no' => 'EXCEL-DEC-001',
        'hearing_date' => now()->startOfMonth()->addDays(5)->toDateString(),
        'hour' => 14,
        'minute' => 30,
        'courtroom' => 'Д',
        'defendants' => 'Шүүгдэгч Excel',
        'notes_handover_text' => 'Шийдвэрийн тойм текст',
        'notes_decided_matter' => 'ЭХТА 10.1',
        'notes_decision_status' => 'Шийдвэрлэсэн',
        'prosecutor_name' => 'Яллагч',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reports.download', [
        'tab' => 'decision_summary',
        'date_from' => $from,
        'date_to' => $to,
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');

    $tempPath = tempnam(sys_get_temp_dir(), 'decision-summary-export-');
    file_put_contents($tempPath, $response->streamedContent());
    $sheet = IOFactory::load($tempPath)->getActiveSheet();

    expect($sheet->getCell('A1')->getValue())->toBe('Шийдвэр: Шийдвэрлэсэн')
        ->and($sheet->getCell('A2')->getValue())->toBe('Огноо')
        ->and($sheet->getCell('N2')->getValue())->toBe('Шүүх хуралдааны шийдвэр')
        ->and($sheet->getCell('D3')->getValue())->toBe('EXCEL-DEC-001')
        ->and($sheet->getCell('G3')->getValue())->toBe('Шүүгдэгч Excel')
        ->and($sheet->getCell('L3')->getValue())->toBe('Шийдвэрийн тойм текст')
        ->and($sheet->getCell('M3')->getValue())->toBe('ЭХТА 10.1')
        ->and($sheet->getCell('N3')->getValue())->toBe('Шийдвэрлэсэн');

    @unlink($tempPath);
});

it('defines fourteen export columns matching the report table', function () {
    $columns = (new DecisionSummaryReportService)->exportColumns();

    expect($columns)->toHaveCount(14)
        ->and(collect($columns)->pluck('label')->all())->toBe([
            'Огноо',
            'Цаг',
            'Танхим',
            'Хэргийн дугаар',
            'Шүүх бүрэлдэхүүн болон шүүгчийн нэр',
            'Улсын яллагч',
            'Шүүгдэгчийн нэр',
            'Зүйл анги',
            'Өмгөөлөгчийн нэр',
            'ТСАХ',
            'Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч',
            'Шийдвэрийн тойм',
            'Шийдвэрлэсэн зүйл анги',
            'Шүүх хуралдааны шийдвэр',
        ]);
});
