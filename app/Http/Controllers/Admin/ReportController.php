<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Reports\Contracts\ReportExportServiceInterface;
use App\Services\Reports\DefendantDetailReportService;
use App\Services\Reports\ReportDateFilterService;
use App\Services\Reports\ReportStatisticsService;
use App\Support\HearingDashboardStatistics;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function __construct(
        private readonly ReportDateFilterService $dateFilterService,
        private readonly ReportStatisticsService $statisticsService,
        private readonly DefendantDetailReportService $defendantDetailReportService,
        private readonly ReportExportServiceInterface $reportExportService,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->dateFilterService->resolve($request);
        $base = $this->dateFilterService->buildBaseQuery($filters);

        $total = (clone $base)->count();
        $issued = (clone $base)->where('notes_handover_issued', true)->count();
        $pending = max(0, $total - $issued);

        extract(HearingDashboardStatistics::decisionBreakdown(clone $base), EXTR_SKIP);

        $sentencingStats = $this->statisticsService->buildSentencingStats(clone $base);
        $defendantDetailRows = $this->defendantDetailReportService->buildRows(clone $base);

        $clerks = User::role('court_clerk')->orderBy('name')->get(['id', 'name']);
        $decisionFilterBaseUrl = route('admin.notes.index', array_filter([
            'hearing_date_from' => $filters->dateFrom,
            'hearing_date_to' => $filters->dateTo,
            'clerk_id' => $filters->effectiveClerkId,
        ], fn ($v) => $v !== null && $v !== ''));

        return view('admin.reports.index', [
            'dateFrom' => $filters->dateFrom,
            'dateTo' => $filters->dateTo,
            'clerkId' => $filters->effectiveClerkId,
            'tab' => $filters->tab,
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
            'defendantDetailColumns' => $this->defendantDetailReportService->exportColumns(),
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }

    public function download(Request $request)
    {
        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            return back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $filters = $this->dateFilterService->resolve($request);
        $base = $this->dateFilterService->buildBaseQuery($filters);

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
        $sentencingStats = $this->statisticsService->buildSentencingStats(clone $base);

        return $this->reportExportService->downloadSummary(
            $filters->from,
            $filters->to,
            $total,
            $issued,
            $pending,
            $decisionCounts,
            $sentencingStats
        );
    }

    public function downloadDefendantDetails(Request $request)
    {
        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            return back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $filters = $this->dateFilterService->resolve($request);
        $base = $this->dateFilterService->buildBaseQuery($filters);

        return $this->reportExportService->downloadDefendantDetails(
            $filters->from,
            $filters->to,
            $this->defendantDetailReportService->buildRows(clone $base),
            $this->defendantDetailReportService->exportColumns(),
        );
    }
}
