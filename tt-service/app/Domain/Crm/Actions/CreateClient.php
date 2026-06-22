<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\DTO\CreateClientDTO;
use App\Domain\Crm\Models\Client;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateClient
{
    /**
     * Создать карточку клиента.
     * Проверяет уникальность телефона/email в рамках клуба.
     */
    public function handle(CreateClientDTO $dto): Client
    {
        return DB::transaction(function () use ($dto) {
            // Проверка дубликата по телефону
            if ($dto->phone) {
                $exists = Client::withoutGlobalScopes()
                    ->where('club_id', $dto->clubId)
                    ->where('phone', $dto->phone)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    throw new RuntimeException(
                        "Клиент с телефоном {$dto->phone} уже существует в этом клубе."
                    );
                }
            }

            // Проверка дубликата по email
            if ($dto->email) {
                $exists = Client::withoutGlobalScopes()
                    ->where('club_id', $dto->clubId)
                    ->where('email', $dto->email)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    throw new RuntimeException(
                        "Клиент с email {$dto->email} уже существует в этом клубе."
                    );
                }
            }

            return Client::create([
                'club_id'      => $dto->clubId,
                'first_name'   => $dto->firstName,
                'last_name'    => $dto->lastName,
                'phone'        => $dto->phone,
                'email'        => $dto->email,
                'birth_date'   => $dto->birthDate?->toDateString(),
                'gender'       => $dto->gender,
                'quick_note'   => $dto->quickNote,
                'source'       => $dto->source,
                'created_by'   => $dto->createdBy,
            ]);
        });
    }
}
