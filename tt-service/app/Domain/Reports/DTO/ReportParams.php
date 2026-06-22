<?php

namespace App\Domain\Reports\DTO;

use Carbon\Carbon;

readonly class ReportParams
{
    public function __construct(
        public int     $clubId,
        public Carbon  $dateFrom,
        public Carbon  $dateTo,
        public ?int    $branchId   = null,
        public ?int    $resourceId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'club_id'     => $this->clubId,
            'date_from'   => $this->dateFrom->toDateString(),
            'date_to'     => $this->dateTo->toDateString(),
            'branch_id'   => $this->branchId,
            'resource_id' => $this->resourceId,
        ];
    }
}
