<?php

namespace App\Domain\Identity\DTO;

/**
 * Данные для создания пользователя.
 */
final class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $role,          // owner|admin|user
        public readonly ?int   $clubId = null, // null = superadmin
    ) {}
}
