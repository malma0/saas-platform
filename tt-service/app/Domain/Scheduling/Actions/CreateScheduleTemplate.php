<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\DTO\CreateScheduleTemplateDTO;
use App\Domain\Scheduling\Models\ScheduleTemplate;
use App\Domain\Scheduling\Services\SessionMaterializer;
use Illuminate\Support\Facades\DB;

class CreateScheduleTemplate
{
    public function __construct(
        private readonly SessionMaterializer $materializer = new SessionMaterializer(),
    ) {}

    /**
     * Создаёт шаблон и материализует будущие занятия.
     */
    public function handle(CreateScheduleTemplateDTO $dto): ScheduleTemplate
    {
        return DB::transaction(function () use ($dto) {
            $template = ScheduleTemplate::create([
                'club_id'              => $dto->clubId,
                'branch_id'            => $dto->branchId,
                'service_offering_id'  => $dto->serviceOfferingId,
                'recurrence_rule'      => $dto->recurrenceRule,
                'start_date'           => $dto->startDate->toDateString(),
                'end_date'             => $dto->endDate?->toDateString(),
                'start_time'           => $dto->startTime,
                'duration_minutes'     => $dto->durationMinutes,
                'notes'                => $dto->notes,
            ]);

            // Материализуем занятия на горизонт 90 дней
            $this->materializer->materialize($template, $dto->resourceIds);

            return $template->load('sessions');
        });
    }
}
