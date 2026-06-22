<?php

namespace App\Domain\Booking\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationHold extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'club_id',
        'resource_id',
        'start_at',
        'end_at',
        'expires_at',
        'session_token',
    ];

    protected function casts(): array
    {
        return [
            'start_at'   => 'datetime',
            'end_at'     => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Facilities\Models\Resource::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
