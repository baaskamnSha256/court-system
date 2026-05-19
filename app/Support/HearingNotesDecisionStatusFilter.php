<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class HearingNotesDecisionStatusFilter
{
    public static function applyExcludingResolved(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('notes_decision_status')
                ->orWhereRaw("TRIM(COALESCE(notes_decision_status, '')) = ''")
                ->orWhereRaw('TRIM(notes_decision_status) <> ?', ['Шийдвэрлэсэн']);
        });
    }

    public static function applyExcludingIssued(Builder $query): void
    {
        $query->where('notes_handover_issued', false);
    }

    public static function apply(Builder $query, string $status): void
    {
        $status = trim($status);

        if ($status === '__pending__') {
            $known = HearingDashboardStatistics::decidedStatuses();
            $query->where(function ($q) use ($known) {
                $q->whereNull('notes_decision_status')
                    ->orWhereRaw("TRIM(notes_decision_status) = ''")
                    ->orWhereRaw(
                        'TRIM(notes_decision_status) NOT IN ('.implode(',', array_fill(0, count($known), '?')).')',
                        $known
                    );
            });

            return;
        }

        $query->whereRaw('TRIM(notes_decision_status) = ?', [$status]);
    }

    public static function label(string $status): string
    {
        return $status === '__pending__' ? 'Хүлээгдэж буй' : $status;
    }
}
