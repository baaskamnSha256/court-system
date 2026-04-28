<?php

namespace App\Services\Reports\Contracts;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ReportExportServiceInterface
{
    /**
     * @param  array<string,int>  $decisionCounts
     * @param  array<string,mixed>  $sentencingStats
     */
    public function downloadSummary(Carbon $from, Carbon $to, int $total, int $issued, int $pending, array $decisionCounts, array $sentencingStats): StreamedResponse;

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @param  list<array{key:string,label:string}>  $columns
     */
    public function downloadDefendantDetails(Carbon $from, Carbon $to, array $rows, array $columns): StreamedResponse;
}
