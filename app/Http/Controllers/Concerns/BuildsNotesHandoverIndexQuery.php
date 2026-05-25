<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use App\Support\HearingNotesDecisionStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait BuildsNotesHandoverIndexQuery
{
    /**
     * @param  Builder<Hearing>  $query
     * @return Builder<Hearing>
     */
    protected function notesHandoverIndexQuery(Request $request, Builder $query): Builder
    {
        $query->fromScheduledSince();

        if ($request->filled('notes_decision_status')) {
            HearingNotesDecisionStatusFilter::apply(
                $query,
                trim((string) $request->input('notes_decision_status'))
            );
        }

        if ($request->filled('notes_handover_issued')) {
            $issued = filter_var(
                $request->input('notes_handover_issued'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($issued !== null) {
                $query->where('notes_handover_issued', $issued);
            }

            return $query;
        }

        if (! HearingDashboardStatistics::isDashboardYearListRequest($request)) {
            HearingNotesDecisionStatusFilter::applyExcludingIssued($query);
        }

        return $query;
    }
}
