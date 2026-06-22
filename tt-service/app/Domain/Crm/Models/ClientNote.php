<?php

namespace App\Domain\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNote extends Model
{
    protected $fillable = [
        'client_id',
        'author_id',
        'text',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
