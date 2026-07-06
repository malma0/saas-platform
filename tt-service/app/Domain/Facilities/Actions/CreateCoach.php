<?php

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\DTO\CreateCoachDTO;
use App\Domain\Facilities\Models\Coach;

/**
 * Создание тренера.
 *
 * «Бэкенд»-ресурс (тип «тренер») для бронируемости создаётся автоматически
 * в Coach::created — поэтому action остаётся тонким.
 */
class CreateCoach
{
    public function handle(CreateCoachDTO $dto): Coach
    {
        return Coach::create([
            'club_id'           => $dto->clubId,
            'branch_id'         => $dto->branchId,
            'name'              => $dto->name,
            'specialization'    => $dto->specialization,
            'experience_years'  => $dto->experienceYears,
            'rank'              => $dto->rank,
            'bio'               => $dto->bio,
            'hourly_rate_minor' => $dto->hourlyRateMinor,
            'sort_order'        => $dto->sortOrder,
            'is_active'         => true,
        ]);
    }
}
