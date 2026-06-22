<?php

namespace App\Domain\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPreference extends Model
{
    protected $fillable = [
        'client_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            // JSONB автоматически декодируется в array/object
            'value' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
