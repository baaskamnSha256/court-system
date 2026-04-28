<?php

namespace App\Services\Reports;

use App\Models\Hearing;
use App\Services\Reports\Dto\ReportFiltersDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportDateFilterService
{
    public function resolve(Request $request): ReportFiltersDto
    {
        $hasExplicitDateRange = $request->filled('date_from') && $request->filled('date_to');
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

        return new ReportFiltersDto(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            applyDateFilter: $hasExplicitDateRange,
            tab: $tab,
            clerkId: $clerkId,
            effectiveClerkId: $effectiveClerkId,
            from: Carbon::parse($dateFrom)->startOfDay(),
            to: Carbon::parse($dateTo)->endOfDay(),
        );
    }

    public function buildBaseQuery(ReportFiltersDto $filters): Builder
    {
        $base = Hearing::query();
        if ($filters->applyDateFilter) {
            $base->where(function ($q) use ($filters) {
                $q->whereBetween('hearing_date', [$filters->from->toDateString(), $filters->to->toDateString()])
                    ->orWhereBetween('start_at', [$filters->from, $filters->to]);
            });
        }

        if (! empty($filters->effectiveClerkId)) {
            $base->where('clerk_id', (int) $filters->effectiveClerkId);
        }

        return $base;
    }
}
