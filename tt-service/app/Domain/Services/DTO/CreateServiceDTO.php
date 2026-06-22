<?php

namespace App\Domain\Services\DTO;

/**
 * @param array<int, array{resource_type_id: int, quantity: int}> $resourceRequirements
 * @param array<int, array{amount_minor: int, currency_code: string, valid_from: ?string,
 *         valid_to: ?string, day_of_week: ?int, time_from: ?string, time_to: ?string, priority: int}> $pricingRules
 */
final class CreateServiceDTO
{
    public function __construct(
        public readonly int    $clubId,
        public readonly string $name,
        public readonly int    $durationMinutes,
        public readonly int    $capacity = 1,
        public readonly ?string $description = null,
        public readonly array  $resourceRequirements = [],
        public readonly array  $pricingRules = [],
    ) {}
}
