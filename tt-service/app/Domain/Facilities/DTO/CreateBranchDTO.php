<?php

namespace App\Domain\Facilities\DTO;

final class CreateBranchDTO
{
    public function __construct(
        public readonly int     $clubId,
        public readonly string  $name,
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly string  $timezone = 'Europe/Moscow',
    ) {}
}
