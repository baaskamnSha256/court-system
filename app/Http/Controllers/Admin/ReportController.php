<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\MatterCategory;
use App\Models\User;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

    private const SPECIAL_OUTCOME_LABELS = [
        'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
        'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
        'Хорих ял оногдуулахгүйгээр тэнссэн',
        'Эрүүгийн хариуцлагаас чөлөөлсөн',
    ];

    private const AGE_BUCKET_LABELS = [
        '14_15' => '14-15',
        '16_17' => '16-17',
        '18_21' => '18-21',
        '22_29' => '22-29',
        '30_34' => '30-34',
        '35_plus' => '35-аас дээш',
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
        $defendantDetailRows = $this->buildDefendantDetailRows(clone $base);

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
            'specialOutcomeRows' => $sentencingStats['specialOutcomeRows'],
            'ageGenderRows' => $sentencingStats['ageGenderRows'],
            'ageGenderHighlights' => $sentencingStats['ageGenderHighlights'],
            'form75Rows' => $sentencingStats['form75Rows'],
            'defendantDetailRows' => $defendantDetailRows,
            'defendantDetailColumns' => self::defendantDetailExportColumns(),
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

    public function downloadDefendantDetails(Request $request)
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

        $rows = $this->buildDefendantDetailRows(clone $base);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Шүүгдэгч дэлгэрэнгүй');
        $columns = self::defendantDetailExportColumns();
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
                $value = $row[$key] ?? '';
                if (in_array($key, ['hearing_id', 'community_hours_total', 'fine_units_total', 'damage_amount_total'], true)) {
                    $value = (int) $value;
                }
                $sheet->setCellValue("{$colLetter}{$line}", $value);
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

    /**
     * «Шүүгдэгчийн дэлгэрэнгүй файл» Excel болон тайлан UI-ийн баганыг нэгтгэсэн жагсаалт.
     *
     * @return list<array{key: string, label: string}>
     */
    private static function defendantDetailExportColumns(): array
    {
        return [
            ['key' => 'hearing_id', 'label' => 'Хурал ID'],
            ['key' => 'case_no', 'label' => 'Хэргийн №'],
            ['key' => 'title', 'label' => 'Гарчиг'],
            ['key' => 'hearing_date', 'label' => 'Хурал огноо'],
            ['key' => 'start_at', 'label' => 'Хурал эхлэх'],
            ['key' => 'courtroom', 'label' => 'Танхим'],
            ['key' => 'decision_status', 'label' => 'Ерөнхий шийдвэр'],
            ['key' => 'defendant_name', 'label' => 'Шүүгдэгч'],
            ['key' => 'defendant_registry', 'label' => 'Регистр'],
            ['key' => 'outcome_track', 'label' => '4 таб: зам'],
            ['key' => 'special_outcome', 'label' => 'Тусгай шийдвэр'],
            ['key' => 'termination_kind', 'label' => 'Дуусгавар төрөл'],
            ['key' => 'termination_note', 'label' => 'Дуусгавар тайлбар'],
            ['key' => 'decided_matter', 'label' => 'Шийдвэрлэсэн зүйл анги'],
            ['key' => 'punishment_types', 'label' => 'Ялын төрлүүд'],
            ['key' => 'community_hours_total', 'label' => 'Нийтэд тустай ажил (цаг)'],
            ['key' => 'fine_units_total', 'label' => 'Торгох нэгж (нийт)'],
            ['key' => 'damage_amount_total', 'label' => 'Мөнгөн дүн (төг, нийт)'],
            ['key' => 'allocations', 'label' => 'Allocations'],
            ['key' => 'raw_sentence_json', 'label' => 'Raw JSON'],
        ];
    }

    private function buildDefendantDetailRows($base): array
    {
        $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
        $rows = [];
        $hearings = $base->orderBy('hearing_date')->orderBy('id')->get([
            'id',
            'case_no',
            'title',
            'hearing_date',
            'start_at',
            'courtroom',
            'notes_decision_status',
            'notes_defendant_sentences',
        ]);

        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : [];
            foreach ($sentences as $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }
                $decidedMatterIds = collect($sentence['decided_matter_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $decidedMatterNames = collect($decidedMatterIds)
                    ->map(fn ($id) => $matterMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $punishments = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
                $allocations = is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [];
                $communityHoursTotal = (int) ($punishments['community_service']['hours'] ?? 0);
                $fineUnitsTotal = (int) ($punishments['fine']['fine_units'] ?? 0);
                $damageAmountTotal = (int) ($punishments['fine']['damage_amount'] ?? 0);
                $allocationText = collect($allocations)->map(function ($allocation) use ($matterMap, &$communityHoursTotal, &$fineUnitsTotal, &$damageAmountTotal) {
                    if (! is_array($allocation)) {
                        return null;
                    }
                    $matterName = $matterMap[(int) ($allocation['matter_category_id'] ?? 0)] ?? '—';
                    $allocationPunishments = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
                    $communityHoursTotal += (int) ($allocationPunishments['community_service']['hours'] ?? 0);
                    $fineUnitsTotal += (int) ($allocationPunishments['fine']['fine_units'] ?? 0);
                    $damageAmountTotal += (int) ($allocationPunishments['fine']['damage_amount'] ?? 0);

                    return $matterName.': '.implode(', ', array_keys($allocationPunishments));
                })->filter()->values()->all();

                $rows[] = [
                    'hearing_id' => (int) $hearing->id,
                    'case_no' => (string) ($hearing->case_no ?? ''),
                    'title' => (string) ($hearing->title ?? ''),
                    'hearing_date' => (string) ($hearing->hearing_date ?? ''),
                    'start_at' => $hearing->start_at ? (string) Carbon::parse($hearing->start_at)->format('Y-m-d H:i') : '',
                    'courtroom' => (string) ($hearing->courtroom ?? ''),
                    'decision_status' => (string) ($hearing->notes_decision_status ?? ''),
                    'defendant_name' => (string) ($sentence['defendant_name'] ?? ''),
                    'defendant_registry' => (string) ($sentence['defendant_registry'] ?? ''),
                    'outcome_track' => (string) ($sentence['outcome_track'] ?? ''),
                    'special_outcome' => (string) ($sentence['special_outcome'] ?? ''),
                    'termination_kind' => (string) ($sentence['termination_kind'] ?? ''),
                    'termination_note' => (string) ($sentence['termination_note'] ?? ''),
                    'decided_matter' => implode(', ', $decidedMatterNames),
                    'punishment_types' => implode(', ', array_keys($punishments)),
                    'community_hours_total' => $communityHoursTotal,
                    'fine_units_total' => $fineUnitsTotal,
                    'damage_amount_total' => $damageAmountTotal,
                    'allocations' => implode(' | ', $allocationText),
                    'raw_sentence_json' => json_encode($sentence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }

        return $rows;
    }

    private function buildSentencingStats($base): array
    {
        $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
        $punishmentCounts = [];
        $articleMetrics = [];
        $crossCounts = [];
        $specialOutcomeCounts = [];
        $ageGenderCounts = [];
        foreach (self::AGE_BUCKET_LABELS as $bucketKey => $_) {
            $ageGenderCounts[$bucketKey] = ['female' => 0, 'male' => 0, 'female_55_plus' => 0, 'male_60_plus' => 0];
        }

        $hearings = $base->get(['notes_defendant_sentences']);
        $convictedTotal = 0;
        $convictedPeopleTotal = 0;
        $convictedLegalEntityTotal = 0;
        $imprisonmentPeopleTotal = 0;
        $imprisonmentFemale = 0;
        $imprisonmentMinor = 0;
        $imprisonmentExecuteTotal = 0;
        $finePeopleTotal = 0;
        $fineLegalEntityTotal = 0;
        $fineAmountTotal = 0;
        $communityServicePeopleTotal = 0;
        $travelRestrictionPeopleTotal = 0;
        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : [];
            foreach ($sentences as $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }
                $registry = (string) ($sentence['defendant_registry'] ?? '');
                $demographics = $this->parseDemographicsFromRegistry($registry, $hearing->hearing_date);
                $isPerson = $demographics !== null;
                $specialOutcome = trim((string) ($sentence['special_outcome'] ?? ''));
                $track = trim((string) ($sentence['outcome_track'] ?? ''));
                if (! in_array($track, ['sentence', 'no_sentence', 'termination'], true)) {
                    if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                        $track = 'no_sentence';
                    } elseif (trim((string) ($sentence['termination_kind'] ?? '')) !== '' || trim((string) ($sentence['termination_note'] ?? '')) !== '') {
                        $track = 'termination';
                    } else {
                        $track = 'sentence';
                    }
                }
                $terminationKind = trim((string) ($sentence['termination_kind'] ?? ''));
                $sentenceMatterIds = collect($sentence['decided_matter_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                if (empty($sentenceMatterIds)) {
                    $sentenceMatterIds = collect($sentence['allocations'] ?? [])
                        ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                        ->filter(fn ($id) => $id > 0)
                        ->unique()
                        ->values()
                        ->all();
                }
                $sentenceMatterNames = collect($sentenceMatterIds)
                    ->map(fn ($id) => $matterMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();
                foreach ($sentenceMatterNames as $articleName) {
                    $articleMetrics[$articleName] ??= [
                        'count' => 0,
                        'sentence_count' => 0,
                        'no_sentence_count' => 0,
                        'termination_count' => 0,
                        'dismiss_count' => 0,
                        'acquit_count' => 0,
                        'special_outcome_count' => 0,
                        'community_hours_total' => 0,
                        'fine_units_total' => 0,
                        'damage_amount_total' => 0,
                    ];
                    $articleMetrics[$articleName]['count']++;
                    if ($track === 'sentence') {
                        $articleMetrics[$articleName]['sentence_count']++;
                    } elseif ($track === 'no_sentence') {
                        $articleMetrics[$articleName]['no_sentence_count']++;
                    } elseif ($track === 'termination') {
                        $articleMetrics[$articleName]['termination_count']++;
                        if ($terminationKind === 'dismiss') {
                            $articleMetrics[$articleName]['dismiss_count']++;
                        } elseif ($terminationKind === 'acquit') {
                            $articleMetrics[$articleName]['acquit_count']++;
                        }
                    }
                    if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                        $articleMetrics[$articleName]['special_outcome_count']++;
                    }
                }
                if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                    $specialOutcomeCounts[$specialOutcome] = (int) ($specialOutcomeCounts[$specialOutcome] ?? 0) + 1;

                    continue;
                }
                $hasSentencePunishment = ! empty($sentence['punishments']) || ! empty($sentence['allocations']);
                if ($hasSentencePunishment) {
                    $convictedTotal++;
                    if ($isPerson) {
                        $convictedPeopleTotal++;
                        $bucket = $demographics['age_bucket'];
                        $gender = $demographics['gender'];
                        $ageGenderCounts[$bucket][$gender] = (int) ($ageGenderCounts[$bucket][$gender] ?? 0) + 1;
                        $age = $demographics['age'];
                        if ($gender === 'female' && $age >= 55) {
                            $ageGenderCounts['35_plus']['female_55_plus'] = (int) ($ageGenderCounts['35_plus']['female_55_plus'] ?? 0) + 1;
                        }
                        if ($gender === 'male' && $age >= 60) {
                            $ageGenderCounts['35_plus']['male_60_plus'] = (int) ($ageGenderCounts['35_plus']['male_60_plus'] ?? 0) + 1;
                        }
                    } else {
                        $convictedLegalEntityTotal++;
                    }
                }
                $allocations = is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [];
                $hasImprisonmentInSentence = false;
                $hasFineInSentence = false;
                $hasCommunityServiceInSentence = false;
                $hasTravelRestrictionInSentence = false;
                $sentenceFineDamageAmount = 0;
                if (! empty($allocations)) {
                    foreach ($allocations as $allocation) {
                        if (! is_array($allocation)) {
                            continue;
                        }
                        $punishments = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
                        $articleName = $matterMap[(int) ($allocation['matter_category_id'] ?? 0)] ?? null;
                        if ($articleName === null) {
                            continue;
                        }
                        $articleMetrics[$articleName] ??= [
                            'count' => 0,
                            'sentence_count' => 0,
                            'no_sentence_count' => 0,
                            'termination_count' => 0,
                            'dismiss_count' => 0,
                            'acquit_count' => 0,
                            'special_outcome_count' => 0,
                            'community_hours_total' => 0,
                            'fine_units_total' => 0,
                            'damage_amount_total' => 0,
                        ];
                        $articleMetrics[$articleName]['damage_amount_total'] += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        $articleMetrics[$articleName]['fine_units_total'] += (int) (($punishments['fine']['fine_units'] ?? 0));
                        $articleMetrics[$articleName]['community_hours_total'] += (int) (($punishments['community_service']['hours'] ?? 0));
                        foreach (array_keys($punishments) as $punishmentKey) {
                            $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                            $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                            if ($punishmentKey === 'imprisonment_open' || $punishmentKey === 'imprisonment_closed') {
                                $hasImprisonmentInSentence = true;
                            }
                            if ($punishmentKey === 'fine') {
                                $hasFineInSentence = true;
                                $sentenceFineDamageAmount += (int) (($punishments['fine']['damage_amount'] ?? 0));
                            }
                            if ($punishmentKey === 'community_service') {
                                $hasCommunityServiceInSentence = true;
                            }
                            if ($punishmentKey === 'travel_restriction') {
                                $hasTravelRestrictionInSentence = true;
                            }
                            $crossKey = $label.'|'.$articleName;
                            $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                        }
                    }
                } else {
                    $punishments = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
                    $articleNames = collect($sentence['decided_matter_ids'] ?? [])
                        ->map(fn ($id) => $matterMap[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    foreach ($articleNames as $articleName) {
                        $articleMetrics[$articleName] ??= [
                            'count' => 0,
                            'sentence_count' => 0,
                            'no_sentence_count' => 0,
                            'termination_count' => 0,
                            'dismiss_count' => 0,
                            'acquit_count' => 0,
                            'special_outcome_count' => 0,
                            'community_hours_total' => 0,
                            'fine_units_total' => 0,
                            'damage_amount_total' => 0,
                        ];
                        $articleMetrics[$articleName]['damage_amount_total'] += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        $articleMetrics[$articleName]['fine_units_total'] += (int) (($punishments['fine']['fine_units'] ?? 0));
                        $articleMetrics[$articleName]['community_hours_total'] += (int) (($punishments['community_service']['hours'] ?? 0));
                    }

                    foreach (array_keys($punishments) as $punishmentKey) {
                        $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                        $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                        if ($punishmentKey === 'imprisonment_open' || $punishmentKey === 'imprisonment_closed') {
                            $hasImprisonmentInSentence = true;
                        }
                        if ($punishmentKey === 'fine') {
                            $hasFineInSentence = true;
                            $sentenceFineDamageAmount += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        }
                        if ($punishmentKey === 'community_service') {
                            $hasCommunityServiceInSentence = true;
                        }
                        if ($punishmentKey === 'travel_restriction') {
                            $hasTravelRestrictionInSentence = true;
                        }
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

                if ($hasImprisonmentInSentence) {
                    $imprisonmentExecuteTotal++;
                    if ($isPerson) {
                        $imprisonmentPeopleTotal++;
                        if (($demographics['gender'] ?? null) === 'female') {
                            $imprisonmentFemale++;
                        }
                        if (($demographics['age'] ?? 0) < 18) {
                            $imprisonmentMinor++;
                        }
                    }
                }
                if ($hasFineInSentence) {
                    if ($isPerson) {
                        $finePeopleTotal++;
                    } else {
                        $fineLegalEntityTotal++;
                    }
                    $fineAmountTotal += $sentenceFineDamageAmount;
                }
                if ($hasCommunityServiceInSentence && $isPerson) {
                    $communityServicePeopleTotal++;
                }
                if ($hasTravelRestrictionInSentence && $isPerson) {
                    $travelRestrictionPeopleTotal++;
                }
            }
        }

        ksort($punishmentCounts);
        ksort($articleMetrics);
        ksort($crossCounts);
        ksort($specialOutcomeCounts);

        $form75Rows = [
            ['label' => '52. Ял шийтгүүлсэн-бүгд', 'value' => $convictedTotal],
            ['label' => '53. Ял шийтгүүлсэн хүн-бүгд', 'value' => $convictedPeopleTotal],
            ['label' => '54. Нас: 14-15', 'value' => (int) (($ageGenderCounts['14_15']['female'] ?? 0) + ($ageGenderCounts['14_15']['male'] ?? 0))],
            ['label' => '55. Нас: 16-17', 'value' => (int) (($ageGenderCounts['16_17']['female'] ?? 0) + ($ageGenderCounts['16_17']['male'] ?? 0))],
            ['label' => '56. Нас: 18-21', 'value' => (int) (($ageGenderCounts['18_21']['female'] ?? 0) + ($ageGenderCounts['18_21']['male'] ?? 0))],
            ['label' => '57. Нас: 22-29', 'value' => (int) (($ageGenderCounts['22_29']['female'] ?? 0) + ($ageGenderCounts['22_29']['male'] ?? 0))],
            ['label' => '58. Нас: 30-34', 'value' => (int) (($ageGenderCounts['30_34']['female'] ?? 0) + ($ageGenderCounts['30_34']['male'] ?? 0))],
            ['label' => '59. Нас: 35-аас дээш', 'value' => (int) (($ageGenderCounts['35_plus']['female'] ?? 0) + ($ageGenderCounts['35_plus']['male'] ?? 0))],
            ['label' => '60. Үүнээс 55+ насны эмэгтэй', 'value' => (int) ($ageGenderCounts['35_plus']['female_55_plus'] ?? 0)],
            ['label' => '61. Үүнээс 60+ насны эрэгтэй', 'value' => (int) ($ageGenderCounts['35_plus']['male_60_plus'] ?? 0)],
            ['label' => '62. Ял шийтгүүлсэн хуулийн этгээд-бүгд', 'value' => $convictedLegalEntityTotal],
            ['label' => '63. Хорих ял шийтгүүлсэн хүн-бүгд', 'value' => $imprisonmentPeopleTotal],
            ['label' => '64. Үүнээс эмэгтэй', 'value' => $imprisonmentFemale],
            ['label' => '65. Үүнээс өсвөр насны хүн', 'value' => $imprisonmentMinor],
            ['label' => '66. Хорих ял биелэн эдлүүлэх-бүгд', 'value' => $imprisonmentExecuteTotal],
            ['label' => '67. Торгох ял шийтгүүлсэн (хүн)', 'value' => $finePeopleTotal],
            ['label' => '68. Торгох ял шийтгүүлсэн (хуулийн этгээд)', 'value' => $fineLegalEntityTotal],
            ['label' => '69. Торгуулийн нийт дүн /төг/', 'value' => $fineAmountTotal],
            ['label' => '70. Нийтэд тустай ажил хийх ял', 'value' => $communityServicePeopleTotal],
            ['label' => '71. Зорчих эрхийг хязгаарлах ял', 'value' => $travelRestrictionPeopleTotal],
            ['label' => '72. Хүмүүжлийн чанартай албадлагын арга хэмжээ', 'value' => (int) ($specialOutcomeCounts['Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн'] ?? 0)],
            ['label' => '73. Эмнэлгийн чанартай албадлагын арга хэмжээ', 'value' => (int) ($specialOutcomeCounts['Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн'] ?? 0)],
            ['label' => '74. Хорих ял оногдуулахгүйгээр тэнссэн', 'value' => (int) ($specialOutcomeCounts['Хорих ял оногдуулахгүйгээр тэнссэн'] ?? 0)],
            ['label' => '75. Эрүүгийн хариуцлагаас чөлөөлсөн', 'value' => (int) ($specialOutcomeCounts['Эрүүгийн хариуцлагаас чөлөөлсөн'] ?? 0)],
        ];

        return [
            'punishmentRows' => collect($punishmentCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'articleRows' => collect($articleMetrics)->map(fn ($row, $name) => array_merge(['name' => $name], $row))->values()->all(),
            'crossRows' => collect($crossCounts)->map(function ($count, $key) {
                [$punishment, $article] = explode('|', (string) $key, 2);

                return ['punishment' => $punishment, 'article' => $article, 'count' => (int) $count];
            })->values()->all(),
            'specialOutcomeRows' => collect($specialOutcomeCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'ageGenderRows' => collect(self::AGE_BUCKET_LABELS)->map(function ($label, $bucketKey) use ($ageGenderCounts) {
                $female = (int) ($ageGenderCounts[$bucketKey]['female'] ?? 0);
                $male = (int) ($ageGenderCounts[$bucketKey]['male'] ?? 0);

                return [
                    'age_group' => $label,
                    'female' => $female,
                    'male' => $male,
                    'total' => $female + $male,
                ];
            })->values()->all(),
            'ageGenderHighlights' => [
                'female_55_plus' => (int) (($ageGenderCounts['35_plus']['female_55_plus'] ?? 0)),
                'male_60_plus' => (int) (($ageGenderCounts['35_plus']['male_60_plus'] ?? 0)),
            ],
            'form75Rows' => $form75Rows,
        ];
    }

    /**
     * @return array{gender: 'female'|'male', age_bucket: string, age: int}|null
     */
    private function parseDemographicsFromRegistry(string $registry, $referenceDate): ?array
    {
        $normalized = mb_strtoupper(trim($registry), 'UTF-8');
        $digits = preg_replace('/\D+/', '', $normalized);
        if (! is_string($digits) || strlen($digits) < 8) {
            return null;
        }

        $datePart = substr($digits, 0, 6);
        $yy = (int) substr($datePart, 0, 2);
        $mmRaw = (int) substr($datePart, 2, 2);
        $dd = (int) substr($datePart, 4, 2);
        $mm = $mmRaw;
        $fullYear = 1900 + $yy;
        if ($mmRaw > 20) {
            $mm = $mmRaw - 20;
            $fullYear = 2000 + $yy;
        }
        if (! checkdate($mm, $dd, $fullYear)) {
            return null;
        }

        $genderDigit = (int) substr($digits, 6, 1);
        $gender = $genderDigit % 2 === 0 ? 'female' : 'male';
        $birthDate = Carbon::create($fullYear, $mm, $dd)->startOfDay();
        $asOfDate = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : now()->startOfDay();
        $age = $birthDate->diffInYears($asOfDate);
        $bucket = match (true) {
            $age >= 14 && $age <= 15 => '14_15',
            $age >= 16 && $age <= 17 => '16_17',
            $age >= 18 && $age <= 21 => '18_21',
            $age >= 22 && $age <= 29 => '22_29',
            $age >= 30 && $age <= 34 => '30_34',
            $age >= 35 => '35_plus',
            default => null,
        };
        if ($bucket === null) {
            return null;
        }

        return [
            'gender' => $gender,
            'age_bucket' => $bucket,
            'age' => $age,
        ];
    }
}
