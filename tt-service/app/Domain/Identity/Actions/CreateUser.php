<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\DTO\CreateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Создание пользователя с ролью.
 *
 * Единая точка входа — вызывается из Seeder, Moonshine-ресурса и API.
 */
class CreateUser
{
    public function handle(CreateUserDTO $dto): User
    {
        $user = User::create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => Hash::make($dto->password),
            'club_id'  => $dto->clubId,
        ]);

        $user->assignRole($dto->role);

        return $user;
    }
}
