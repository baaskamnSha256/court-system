<?php

namespace App\Services\Reports\Dto;

use Carbon\Carbon;

readonly class ReportFiltersDto
{
    public function __construct(
        public string $dateFrom,
        public string $dateTo,
        public bool $applyDateFilter,
        public ?string $tab,
        public mixed $clerkId,
        public mixed $effectiveClerkId,
        public Carbon $from,
        public Carbon $to,
    ) {}
}
