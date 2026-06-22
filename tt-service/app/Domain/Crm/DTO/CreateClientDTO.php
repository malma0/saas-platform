<?php

namespace App\Domain\Crm\DTO;

use Carbon\Carbon;

readonly class CreateClientDTO
{
    public function __construct(
        public int     $clubId,
        public string  $firstName,
        public ?string $lastName    = null,
        public ?string $phone       = null,
        public ?string $email       = null,
        public ?Carbon $birthDate   = null,
        public ?string $gender      = null,   // male|female|other
        public ?string $quickNote   = null,
        public ?string $source      = null,
        public ?int    $createdBy   = null,   // user_id кто создал
    ) {}
}
