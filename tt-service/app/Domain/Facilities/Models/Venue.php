<?php

namespace App\Domain\Facilities\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Объект (площадка) внутри филиала.
 * Например: «Зал 1», «Корт А», «Открытая терраса».
 * Venue → Hall (залы/столы внутри площадки).
 */
class Venue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'club_id',
        'branch_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class);
    }
}
