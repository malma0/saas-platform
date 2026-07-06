<?php

namespace App\Domain\Facilities\DTO;

final class CreateCoachDTO
{
    public function __construct(
        public readonly int     $clubId,
        public readonly int     $branchId,
        public readonly string  $name,
        public readonly int     $hourlyRateMinor,
        public readonly ?string $specialization = null,
        public readonly int     $experienceYears = 0,
        public readonly ?string $rank = null,
        public readonly ?string $bio = null,
        public readonly int     $sortOrder = 0,
    ) {}
}
