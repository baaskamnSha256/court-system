<?php

namespace App\Services\Reports;

use App\Services\Reports\Contracts\ReportExportServiceInterface;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService implements ReportExportServiceInterface
{
    /**
     * @param  array<string,int>  $decisionCounts
     * @param  array<string,mixed>  $sentencingStats
     */
    public function downloadSummary(Carbon $from, Carbon $to, int $total, int $issued, int $pending, array $decisionCounts, array $sentencingStats): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Тайлан');

        $sheet->setCellValue('A1', 'Огноо (From)');
        $sheet->setCellValue('B1', $from->format('Y-m-d'));
        $sheet->setCellValue('A2', 'Огноо (To)');
        $sheet->setCellValue('B2', $to->format('Y-m-d'));
        $sheet->setCellValue('A4', 'Нийт');
        $sheet->setCellValue('B4', $total);
        $sheet->setCellValue('A5', 'Тэмдэглэл хүлээлцсэн');
        $sheet->setCellValue('B5', $issued);
        $sheet->setCellValue('A6', 'Тэмдэглэл хүлээлцээгүй');
        $sheet->setCellValue('B6', $pending);

        $sheet->setCellValue('A8', 'Шүүх хуралдааны шийдвэр');
        $sheet->setCellValue('B8', 'Тоо');
        $sheet->getStyle('A8:B8')->getFont()->setBold(true);

        $r = 9;
        foreach ($decisionCounts as $name => $count) {
            $sheet->setCellValue("A{$r}", (string) $name);
            $sheet->setCellValue("B{$r}", (int) $count);
            $r++;
        }

        $r += 2;
        $sheet->setCellValue("A{$r}", 'Ялын төрөл');
        $sheet->setCellValue("B{$r}", 'Тоо');
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['punishmentRows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['name']);
            $sheet->setCellValue("B{$r}", (int) $row['count']);
            $r++;
        }

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Шийдвэрлэсэн зүйл анги');
        $sheet->setCellValue("B{$r}", 'Тоо');
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['articleRows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['name']);
            $sheet->setCellValue("B{$r}", (int) $row['count']);
            $r++;
        }

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Ялын төрөл x Шийдвэрлэсэн зүйл анги');
        $sheet->setCellValue("B{$r}", 'Тоо');
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['crossRows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['punishment'].' | '.$row['article']);
            $sheet->setCellValue("B{$r}", (int) $row['count']);
            $r++;
        }

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Ял биш тусгай шийдвэр');
        $sheet->setCellValue("B{$r}", 'Тоо');
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['specialOutcomeRows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['name']);
            $sheet->setCellValue("B{$r}", (int) $row['count']);
            $r++;
        }

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Нас, хүйсээр ял шийтгэл хүлээсэн');
        $sheet->setCellValue("B{$r}", 'Эмэгтэй');
        $sheet->setCellValue("C{$r}", 'Эрэгтэй');
        $sheet->setCellValue("D{$r}", 'Нийт');
        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['ageGenderRows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['age_group']);
            $sheet->setCellValue("B{$r}", (int) $row['female']);
            $sheet->setCellValue("C{$r}", (int) $row['male']);
            $sheet->setCellValue("D{$r}", (int) $row['total']);
            $r++;
        }
        $sheet->setCellValue("A{$r}", 'Үүнээс: 55-аас дээш насны эмэгтэй');
        $sheet->setCellValue("B{$r}", (int) ($sentencingStats['ageGenderHighlights']['female_55_plus'] ?? 0));
        $sheet->setCellValue("C{$r}", '');
        $sheet->setCellValue("D{$r}", (int) ($sentencingStats['ageGenderHighlights']['female_55_plus'] ?? 0));
        $r++;
        $sheet->setCellValue("A{$r}", 'Үүнээс: 60-аас дээш насны эрэгтэй');
        $sheet->setCellValue("B{$r}", '');
        $sheet->setCellValue("C{$r}", (int) ($sentencingStats['ageGenderHighlights']['male_60_plus'] ?? 0));
        $sheet->setCellValue("D{$r}", (int) ($sentencingStats['ageGenderHighlights']['male_60_plus'] ?? 0));
        $r++;

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Маягтын 52-75 нэгтгэл');
        $sheet->setCellValue("B{$r}", 'Тоо');
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($sentencingStats['form75Rows'] as $row) {
            $sheet->setCellValue("A{$r}", (string) $row['label']);
            $sheet->setCellValue("B{$r}", (int) $row['value']);
            $r++;
        }

        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle("A1:D{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);

        $fileName = 'тайлан_admin_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @param  list<array{key:string,label:string}>  $columns
     */
    public function downloadDefendantDetails(Carbon $from, Carbon $to, array $rows, array $columns): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Шүүгдэгч дэлгэрэнгүй');
        $lastColLetter = Coordinate::stringFromColumnIndex(count($columns));
        foreach ($columns as $index => $column) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$colLetter}1", $column['label']);
        }
        $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);

        $line = 2;
        foreach ($rows as $row) {
            foreach ($columns as $index => $column) {
                $colLetter = Coordinate::stringFromColumnIndex($index + 1);
                $key = $column['key'];
                $sheet->setCellValue("{$colLetter}{$line}", $row[$key] ?? '');
            }
            $line++;
        }

        $lastDataRow = max(1, $line - 1);
        $sheet->setAutoFilter("A1:{$lastColLetter}{$lastDataRow}");
        $sheet->freezePane('A2');
        foreach (range(1, count($columns)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }
        $sheet->getStyle("A1:{$lastColLetter}{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $fileName = 'тайлан_шүүгдэгч_дэлгэрэнгүй_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
