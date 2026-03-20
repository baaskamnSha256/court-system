<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->input('date_to') ?: now()->endOfMonth()->format('Y-m-d');
        $clerkId = $request->input('clerk_id');

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $base = Hearing::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });

        if (!empty($clerkId)) {
            $base->where('clerk_id', (int) $clerkId);
        }

        $total = (clone $base)->count();
        $issued = (clone $base)->where('notes_handover_issued', true)->count();
        $pending = max(0, $total - $issued);

        $decisionCounts = (clone $base)
            ->select(['notes_decision_status'])
            ->whereNotNull('notes_decision_status')
            ->get()
            ->groupBy('notes_decision_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        $decisionOptions = [
            'Шийдвэрлэсэн',
            'Хойшилсон',
            'Завсарласан',
            'Прокурорт буцаасан',
            'Яллагдагчийг шүүхэд шилжүүлсэн',
            '60 хүртэлх хоногоор хойшлуулсан',
        ];

        $decisionRows = [];
        foreach ($decisionOptions as $opt) {
            $decisionRows[] = [
                'name' => $opt,
                'count' => (int) ($decisionCounts[$opt] ?? 0),
            ];
        }

        $clerks = User::role('court_clerk')->orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', [
            'headerTitle' => 'Тайлан',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'clerkId' => $clerkId,
            'clerks' => $clerks,
            'summary' => compact('total', 'issued', 'pending'),
            'decisionRows' => $decisionRows,
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }

    public function download(Request $request)
    {
        if (!$request->filled('date_from') || !$request->filled('date_to')) {
            return back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $clerkId = $request->input('clerk_id');

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $base = Hearing::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });

        if (!empty($clerkId)) {
            $base->where('clerk_id', (int) $clerkId);
        }

        $total = (clone $base)->count();
        $issued = (clone $base)->where('notes_handover_issued', true)->count();
        $pending = max(0, $total - $issued);

        $decisionCounts = (clone $base)
            ->select(['notes_decision_status'])
            ->whereNotNull('notes_decision_status')
            ->get()
            ->groupBy('notes_decision_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        $spreadsheet = new Spreadsheet();
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

        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle("A1:B{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $fileName = 'тайлан_admin_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

