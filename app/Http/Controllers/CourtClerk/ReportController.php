<?php

namespace App\Http\Controllers\CourtClerk;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    private const EXPORT_LIMIT = 5000;

    public function index(Request $request)
    {
        $userId = auth()->id();

        $year = $request->filled('year') ? (int) $request->input('year') : null;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $mode = $year ? 'year' : 'range';

        $summary = [
            'total' => 0,
            'issued' => 0,
            'pending' => 0,
        ];
        $notesFilterFrom = null;
        $notesFilterTo = null;
        $monthlyRows = [];
        $dailyRows = [];

        if ($mode === 'year') {
            $monthlyRows = $this->buildMonthlyRows($userId, $year);
            $summary['total'] = array_sum(array_column($monthlyRows, 'total'));
            $summary['issued'] = array_sum(array_column($monthlyRows, 'issued'));
            $summary['pending'] = max(0, $summary['total'] - $summary['issued']);
            $notesFilterFrom = Carbon::create($year, 1, 1)->toDateString();
            $notesFilterTo = Carbon::create($year, 12, 31)->toDateString();
        } else {
            // Only compute when both dates are present; otherwise show zeros and let user choose.
            if ($dateFrom && $dateTo) {
                $from = Carbon::parse($dateFrom)->startOfDay();
                $to = Carbon::parse($dateTo)->endOfDay();
                $notesFilterFrom = $from->toDateString();
                $notesFilterTo = $to->toDateString();

                $base = $this->baseQuery($userId, $from, $to);

                $summary['total'] = (clone $base)->count();
                $summary['issued'] = (clone $base)->where('notes_handover_issued', true)->count();
                $summary['pending'] = max(0, $summary['total'] - $summary['issued']);

                $dailyRows = (clone $base)
                    ->select(['id', 'hearing_date', 'start_at', 'notes_handover_issued'])
                    ->orderBy('hearing_date')
                    ->orderBy('start_at')
                    ->limit(self::EXPORT_LIMIT)
                    ->get()
                    ->groupBy(function ($h) {
                        $d = $h->hearing_date ?: $h->start_at;

                        return Carbon::parse($d)->format('Y-m-d');
                    })
                    ->map(function ($group, $date) {
                        $total = $group->count();
                        $issued = $group->where('notes_handover_issued', true)->count();

                        return [
                            'date' => $date,
                            'total' => $total,
                            'issued' => $issued,
                            'pending' => max(0, $total - $issued),
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        $years = range((int) now()->format('Y'), (int) now()->subYears(5)->format('Y'));

        return view('court_clerk.reports.index', [
            'headerTitle' => 'Тайлан',
            'mode' => $mode,
            'years' => $years,
            'year' => $year,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summary' => $summary,
            'monthlyRows' => $monthlyRows,
            'dailyRows' => $dailyRows,
            'notesFilterFrom' => $notesFilterFrom,
            'notesFilterTo' => $notesFilterTo,
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }

    public function download(Request $request)
    {
        $userId = auth()->id();
        $year = $request->filled('year') ? (int) $request->input('year') : null;

        if ($year) {
            $rows = $this->buildMonthlyRows($userId, $year);

            return $this->downloadMonthly($rows, $year);
        }

        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            return back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $from = Carbon::parse($request->input('date_from'))->startOfDay();
        $to = Carbon::parse($request->input('date_to'))->endOfDay();
        $base = $this->baseQuery($userId, $from, $to);

        $dailyRows = (clone $base)
            ->select(['id', 'hearing_date', 'start_at', 'notes_handover_issued'])
            ->orderBy('hearing_date')
            ->orderBy('start_at')
            ->limit(self::EXPORT_LIMIT)
            ->get()
            ->groupBy(function ($h) {
                $d = $h->hearing_date ?: $h->start_at;

                return Carbon::parse($d)->format('Y-m-d');
            })
            ->map(function ($group, $date) {
                $total = $group->count();
                $issued = $group->where('notes_handover_issued', true)->count();

                return [
                    'date' => $date,
                    'total' => $total,
                    'issued' => $issued,
                    'pending' => max(0, $total - $issued),
                ];
            })
            ->values()
            ->all();

        return $this->downloadDaily($dailyRows, $from, $to);
    }

    private function baseQuery(int $userId, Carbon $from, Carbon $to)
    {
        return Hearing::query()
            ->where('clerk_id', $userId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });
    }

    private function buildMonthlyRows(int $userId, int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfDay();
            $end = (clone $start)->endOfMonth()->endOfDay();

            $base = $this->baseQuery($userId, $start, $end);
            $total = (clone $base)->count();
            $issued = (clone $base)->where('notes_handover_issued', true)->count();

            $rows[] = [
                'month' => sprintf('%04d-%02d', $year, $m),
                'total' => $total,
                'issued' => $issued,
                'pending' => max(0, $total - $issued),
            ];
        }

        return $rows;
    }

    private function downloadMonthly(array $rows, int $year)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Тайлан');

        $headers = ['Сар', 'Нийт хурал', 'Тэмдэглэл хүлээлцсэн', 'Тэмдэглэл хүлээлцээгүй'];
        $sheet->fromArray($headers, null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row['month']);
            $sheet->setCellValue("B{$r}", $row['total']);
            $sheet->setCellValue("C{$r}", $row['issued']);
            $sheet->setCellValue("D{$r}", $row['pending']);
            $r++;
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle("A1:D{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "тайлан_{$year}.xlsx";
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function downloadDaily(array $rows, Carbon $from, Carbon $to)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Тайлан');

        $sheet->setCellValue('A1', 'Огноо');
        $sheet->setCellValue('B1', 'Нийт хурал');
        $sheet->setCellValue('C1', 'Тэмдэглэл хүлээлцсэн');
        $sheet->setCellValue('D1', 'Тэмдэглэл хүлээлцээгүй');

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row['date']);
            $sheet->setCellValue("B{$r}", $row['total']);
            $sheet->setCellValue("C{$r}", $row['issued']);
            $sheet->setCellValue("D{$r}", $row['pending']);
            $r++;
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle("A1:D{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'тайлан_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
