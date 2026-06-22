<?php

namespace App\Domain\Facilities\DTO;

final class CreateResourceDTO
{
    public function __construct(
        public readonly int     $clubId,
        public readonly int     $branchId,
        public readonly int     $resourceTypeId,
        public readonly string  $name,
        public readonly ?int    $parentId = null,
        public readonly int     $capacity = 1,
    ) {}
}
