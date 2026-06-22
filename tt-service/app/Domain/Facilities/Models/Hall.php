<?php

namespace App\Domain\Facilities\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Зал / стол внутри Venue.
 * В иерархии: Club → Branch → Venue → Hall.
 * Hall в Фазе 4 будет связан с Resource (универсальная модель ресурсов).
 */
class Hall extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'club_id',
        'venue_id',
        'name',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity'  => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
