<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    private const PUNISHMENT_LABELS = [
        'fine' => 'Торгох',
        'community_service' => 'Нийтэд тустай ажил',
        'travel_restriction' => 'Зорчих эрхийг хязгаарлах',
        'imprisonment_open' => 'Хорих (Нээлттэй)',
        'imprisonment_closed' => 'Хорих (Хаалттай)',
        'rights_ban_public_service' => 'Эрх хасах (Нийтийн алба)',
        'rights_ban_professional_activity' => 'Эрх хасах (Мэргэжлийн үйл ажиллагаа)',
        'rights_ban_driving' => 'Эрх хасах (Жолоодох эрх)',
    ];

    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->input('date_to') ?: now()->endOfMonth()->format('Y-m-d');
        $clerkId = $request->input('clerk_id');
        $tabInput = $request->input('tab');
        $tab = is_string($tabInput) ? $tabInput : null;
        $allowedTabs = ['notes_handover', 'decision_summary', 'article', 'punishment'];
        if ($tab !== null && ! in_array($tab, $allowedTabs, true)) {
            $tab = null;
        }

        $applyClerkFilter = $tab === 'notes_handover';
        $effectiveClerkId = $applyClerkFilter ? $clerkId : null;

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $base = Hearing::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });

        if (! empty($effectiveClerkId)) {
            $base->where('clerk_id', (int) $effectiveClerkId);
        }

        $total = (clone $base)->count();
        $issued = (clone $base)->where('notes_handover_issued', true)->count();
        $pending = max(0, $total - $issued);

        extract(HearingDashboardStatistics::decisionBreakdown(clone $base), EXTR_SKIP);

        $sentencingStats = $this->buildSentencingStats(clone $base);

        $clerks = User::role('court_clerk')->orderBy('name')->get(['id', 'name']);
        $decisionFilterBaseUrl = route('admin.notes.index', array_filter([
            'hearing_date_from' => $dateFrom,
            'hearing_date_to' => $dateTo,
            'clerk_id' => $effectiveClerkId,
        ], fn ($v) => $v !== null && $v !== ''));

        return view('admin.reports.index', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'clerkId' => $effectiveClerkId,
            'tab' => $tab,
            'clerks' => $clerks,
            'summary' => compact('total', 'issued', 'pending'),
            'decisionOptions' => $decisionOptions,
            'decisionCounts' => $decisionCounts,
            'decisionFilterBaseUrl' => $decisionFilterBaseUrl,
            'punishmentRows' => $sentencingStats['punishmentRows'],
            'articleRows' => $sentencingStats['articleRows'],
            'crossRows' => $sentencingStats['crossRows'],
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }

    public function download(Request $request)
    {
        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            return back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $clerkId = $request->input('clerk_id');
        $tabInput = $request->input('tab');
        $tab = is_string($tabInput) ? $tabInput : null;
        $applyClerkFilter = $tab === 'notes_handover';
        $effectiveClerkId = $applyClerkFilter ? $clerkId : null;

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $base = Hearing::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('hearing_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('start_at', [$from, $to]);
            });

        if (! empty($effectiveClerkId)) {
            $base->where('clerk_id', (int) $effectiveClerkId);
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
        $sentencingStats = $this->buildSentencingStats(clone $base);

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

        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle("A1:B{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $fileName = 'тайлан_admin_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildSentencingStats($base): array
    {
        $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
        $punishmentCounts = [];
        $articleCounts = [];
        $crossCounts = [];

        $hearings = $base->get(['notes_defendant_sentences']);
        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : [];
            foreach ($sentences as $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }
                $allocations = is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [];
                if (! empty($allocations)) {
                    foreach ($allocations as $allocation) {
                        if (! is_array($allocation)) {
                            continue;
                        }
                        $articleName = $matterMap[(int) ($allocation['matter_category_id'] ?? 0)] ?? null;
                        if ($articleName === null) {
                            continue;
                        }
                        $articleCounts[$articleName] = (int) ($articleCounts[$articleName] ?? 0) + 1;
                        $punishments = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
                        foreach (array_keys($punishments) as $punishmentKey) {
                            $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                            $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                            $crossKey = $label.'|'.$articleName;
                            $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                        }
                    }

                    continue;
                }

                $articleNames = collect($sentence['decided_matter_ids'] ?? [])
                    ->map(fn ($id) => $matterMap[(int) $id] ?? null)
                    ->filter()
                    ->values()
                    ->all();
                foreach ($articleNames as $articleName) {
                    $articleCounts[$articleName] = (int) ($articleCounts[$articleName] ?? 0) + 1;
                }

                $punishments = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
                foreach (array_keys($punishments) as $punishmentKey) {
                    $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                    $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                    if (empty($articleNames)) {
                        $crossKey = $label.'|—';
                        $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                    } else {
                        foreach ($articleNames as $articleName) {
                            $crossKey = $label.'|'.$articleName;
                            $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        ksort($punishmentCounts);
        ksort($articleCounts);
        ksort($crossCounts);

        return [
            'punishmentRows' => collect($punishmentCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'articleRows' => collect($articleCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'crossRows' => collect($crossCounts)->map(function ($count, $key) {
                [$punishment, $article] = explode('|', (string) $key, 2);

                return ['punishment' => $punishment, 'article' => $article, 'count' => (int) $count];
            })->values()->all(),
        ];
    }
}
