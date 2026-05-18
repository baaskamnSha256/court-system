<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait ScopesHearingsFromToday
{
    /**
     * @param  Builder<Hearing>  $query
     * @return Builder<Hearing>
     */
    protected function applyHearingsVisibleFromToday(Builder $query, ?Carbon $today = null): Builder
    {
        return $query->fromTodayOnwards($today ?? Carbon::today());
    }
}
