<?php

namespace App\Domain\Scheduling\DTO;

use Carbon\Carbon;

readonly class CreateScheduleTemplateDTO
{
    /**
     * @param array<int> $resourceIds   Конкретные ресурсы для резерва
     */
    public function __construct(
        public int     $clubId,
        public int     $branchId,
        public int     $serviceOfferingId,
        public string  $recurrenceRule,   // RRULE: FREQ=WEEKLY;BYDAY=TU,TH
        public Carbon  $startDate,
        public ?Carbon $endDate,
        public string  $startTime,        // HH:MM (локальное время филиала)
        public int     $durationMinutes,
        public array   $resourceIds,
        public ?string $notes = null,
    ) {}
}
