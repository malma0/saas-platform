<?php

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\WorkingHour;
use Illuminate\Support\Facades\DB;

/**
 * Создание филиала с расписанием работы по умолчанию.
 */
class CreateBranch
{
    /**
     * @param  array<int, array{open_time: string, close_time: string, is_closed: bool}>|null  $workingHours
     *         Ключ — day_of_week (0–6). Если null — создаётся стандартное пн–сб 09:00–22:00, вс — выходной.
     */
    public function handle(CreateBranchDTO $dto, ?array $workingHours = null): Branch
    {
        return DB::transaction(function () use ($dto, $workingHours) {
            $branch = Branch::create([
                'club_id'   => $dto->clubId,
                'name'      => $dto->name,
                'address'   => $dto->address,
                'phone'     => $dto->phone,
                'email'     => $dto->email,
                'timezone'  => $dto->timezone,
                'is_active' => true,
            ]);

            $this->createWorkingHours($branch, $workingHours ?? $this->defaultWorkingHours());

            return $branch->load('workingHours');
        });
    }

    private function createWorkingHours(Branch $branch, array $hours): void
    {
        foreach ($hours as $day => $config) {
            WorkingHour::create([
                'branch_id'   => $branch->id,
                'day_of_week' => $day,
                'open_time'   => $config['open_time'] ?? null,
                'close_time'  => $config['close_time'] ?? null,
                'is_closed'   => $config['is_closed'] ?? false,
            ]);
        }
    }

    /**
     * Стандартное расписание: Пн–Сб 09:00–22:00, Вс — выходной.
     */
    private function defaultWorkingHours(): array
    {
        $schedule = [];
        for ($day = 0; $day <= 6; $day++) {
            $schedule[$day] = $day === 0
                ? ['open_time' => null,    'close_time' => null,    'is_closed' => true]
                : ['open_time' => '09:00', 'close_time' => '22:00', 'is_closed' => false];
        }

        return $schedule;
    }
}
