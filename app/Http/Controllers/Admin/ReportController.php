<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatterCategory;
use App\Models\User;
use App\Services\Reports\Contracts\ReportExportServiceInterface;
use App\Services\Reports\DecisionSummaryReportService;
use App\Services\Reports\DefendantDetailReportService;
use App\Services\Reports\ReportDateFilterService;
use App\Services\Reports\ReportStatisticsService;
use App\Support\HearingDashboardStatistics;
use App\Support\HearingNotesDecisionStatusFilter;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function __construct(
        private readonly ReportDateFilterService $dateFilterService,
        private readonly ReportStatisticsService $statisticsService,
        private readonly DefendantDetailReportService $defendantDetailReportService,
        private readonly DecisionSummaryReportService $decisionSummaryReportService,
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
        $defendantDetailRowsRaw = $this->defendantDetailReportService->buildRows(clone $base);
        $defendantDetailNestedGroups = $this->defendantDetailReportService->buildNestedPreviewGroups($defendantDetailRowsRaw);

        $clerks = User::role('court_clerk')->orderBy('name')->get(['id', 'name']);
        $decisionFilterBaseUrl = route('admin.reports.index', array_filter([
            'tab' => 'decision_summary',
            'date_from' => $filters->dateFrom,
            'date_to' => $filters->dateTo,
        ], fn ($v) => $v !== null && $v !== ''));

        $decisionStatusFilter = $request->filled('notes_decision_status')
            ? trim((string) $request->input('notes_decision_status'))
            : null;
        $decisionSummaryHearings = null;
        if ($filters->tab === 'decision_summary') {
            $decisionListQuery = clone $base;
            if ($decisionStatusFilter !== null && $decisionStatusFilter !== '') {
                HearingNotesDecisionStatusFilter::apply($decisionListQuery, $decisionStatusFilter);
            }
            $decisionSummaryHearings = $decisionListQuery
                ->with(['judges', 'prosecutor'])
                ->orderBy('hearing_date')
                ->orderBy('hour')
                ->orderBy('minute')
                ->orderBy('courtroom')
                ->paginate(25)
                ->withQueryString();
        }

        $matterNamesById = MatterCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $totalScheduledHearingsInPeriod = $total;
        $totalScheduledHearingsUrl = route('admin.reports.index', array_filter([
            'tab' => 'decision_summary',
            'date_from' => $filters->dateFrom,
            'date_to' => $filters->dateTo,
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
            'totalScheduledHearingsInPeriod' => $totalScheduledHearingsInPeriod,
            'totalScheduledHearingsUrl' => $totalScheduledHearingsUrl,
            'decisionStatusFilter' => $decisionStatusFilter,
            'decisionStatusFilterLabel' => $decisionStatusFilter !== null && $decisionStatusFilter !== ''
                ? HearingNotesDecisionStatusFilter::label($decisionStatusFilter)
                : null,
            'decisionSummaryHearings' => $decisionSummaryHearings,
            'matterNamesById' => $matterNamesById,
            'punishmentRows' => $sentencingStats['punishmentRows'],
            'articleRows' => $sentencingStats['articleRows'],
            'articleTableColumns' => $sentencingStats['articleTableColumns'],
            'crossRows' => $sentencingStats['crossRows'],
            'specialOutcomeRows' => $sentencingStats['specialOutcomeRows'],
            'ageGenderRows' => $sentencingStats['ageGenderRows'],
            'ageGenderHighlights' => $sentencingStats['ageGenderHighlights'],
            'form75Rows' => $sentencingStats['form75Rows'],
            'defendantDetailRowCount' => count($defendantDetailRowsRaw),
            'defendantDetailNestedGroups' => $defendantDetailNestedGroups,
            'defendantDetailColumns' => $this->defendantDetailReportService->exportColumns(),
            'defendantDetailColumnGroups' => $this->defendantDetailReportService->uiRowspanColumnGroups(),
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

        if ($filters->tab === 'decision_summary') {
            $decisionStatusFilter = $request->filled('notes_decision_status')
                ? trim((string) $request->input('notes_decision_status'))
                : null;
            $exportQuery = clone $base;
            if ($decisionStatusFilter !== null && $decisionStatusFilter !== '') {
                HearingNotesDecisionStatusFilter::apply($exportQuery, $decisionStatusFilter);
            }
            $matterNamesById = MatterCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name', 'id');

            return $this->reportExportService->downloadDecisionSummary(
                $filters->from,
                $filters->to,
                $this->decisionSummaryReportService->buildExportRows(
                    $exportQuery,
                    $matterNamesById,
                    self::EXPORT_LIMIT
                ),
                $this->decisionSummaryReportService->exportColumns(),
                $decisionStatusFilter !== null && $decisionStatusFilter !== ''
                    ? HearingNotesDecisionStatusFilter::label($decisionStatusFilter)
                    : null,
            );
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
