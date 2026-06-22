<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Client;
use RuntimeException;

class BlockClient
{
    public function block(Client $client, string $reason): Client
    {
        if ($client->is_blocked) {
            throw new RuntimeException('Клиент уже заблокирован.');
        }

        $client->update([
            'is_blocked'   => true,
            'block_reason' => $reason,
        ]);

        return $client->fresh();
    }

    public function unblock(Client $client): Client
    {
        if (! $client->is_blocked) {
            throw new RuntimeException('Клиент не заблокирован.');
        }

        $client->update([
            'is_blocked'   => false,
            'block_reason' => null,
        ]);

        return $client->fresh();
    }
}
