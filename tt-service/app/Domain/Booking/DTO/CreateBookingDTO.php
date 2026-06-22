<?php

namespace App\Domain\Booking\DTO;

use Carbon\Carbon;

final class CreateBookingDTO
{
    /**
     * @param array<int> $resourceIds  конкретные ресурсы, которые нужно занять
     */
    public function __construct(
        public readonly int     $clubId,
        public readonly int     $branchId,
        public readonly int     $serviceOfferingId,
        public readonly array   $resourceIds,
        public readonly Carbon  $startAt,       // UTC
        public readonly Carbon  $endAt,         // UTC
        public readonly int     $amountMinor,   // копейки
        public readonly string  $currencyCode = 'RUB',
        public readonly ?int    $clientId = null,
        public readonly ?int    $adminId = null,
        public readonly ?string $notes = null,
        // Токен hold'а, который нужно снять после создания брони
        public readonly ?string $ignoreHoldToken = null,
    ) {}
}
