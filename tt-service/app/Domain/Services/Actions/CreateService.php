<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\DTO\CreateServiceDTO;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Models\ServiceResourceRequirement;
use Illuminate\Support\Facades\DB;

/**
 * Создание услуги с требованиями к ресурсам и правилами цен.
 *
 * Единая транзакция: service + requirements + pricing rules.
 */
class CreateService
{
    public function handle(CreateServiceDTO $dto): ServiceOffering
    {
        return DB::transaction(function () use ($dto) {
            $service = ServiceOffering::create([
                'club_id'          => $dto->clubId,
                'name'             => $dto->name,
                'description'      => $dto->description,
                'duration_minutes' => $dto->durationMinutes,
                'capacity'         => $dto->capacity,
                'is_active'        => true,
            ]);

            // Требования к ресурсам
            foreach ($dto->resourceRequirements as $req) {
                ServiceResourceRequirement::create([
                    'service_offering_id' => $service->id,
                    'resource_type_id'    => $req['resource_type_id'],
                    'quantity'            => $req['quantity'] ?? 1,
                ]);
            }

            // Правила ценообразования
            foreach ($dto->pricingRules as $rule) {
                PricingRule::create([
                    'service_offering_id' => $service->id,
                    'amount_minor'        => $rule['amount_minor'],
                    'currency_code'       => $rule['currency_code'] ?? 'RUB',
                    'valid_from'          => $rule['valid_from']  ?? null,
                    'valid_to'            => $rule['valid_to']    ?? null,
                    'day_of_week'         => $rule['day_of_week'] ?? null,
                    'time_from'           => $rule['time_from']   ?? null,
                    'time_to'             => $rule['time_to']     ?? null,
                    'priority'            => $rule['priority']    ?? 0,
                ]);
            }

            return $service->load(['resourceRequirements', 'pricingRules']);
        });
    }
}
