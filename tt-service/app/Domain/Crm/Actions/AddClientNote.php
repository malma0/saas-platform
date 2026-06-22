<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Client;
use App\Domain\Crm\Models\ClientNote;

class AddClientNote
{
    public function handle(
        Client  $client,
        string  $text,
        ?int    $authorId = null,
        bool    $isPinned = false,
    ): ClientNote {
        return $client->notes()->create([
            'author_id' => $authorId,
            'text'      => $text,
            'is_pinned' => $isPinned,
        ]);
    }
}
