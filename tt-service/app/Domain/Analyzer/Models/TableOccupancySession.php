<?php

namespace App\Domain\Analyzer\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Факт физической занятости стола — записывается Python-анализатором.
 *
 * Архитектурное правило: эта таблица — единственная точка связи
 * между анализатором и Laravel-системой. Нет прямых вызовов Python из PHP.
 */
class TableOccupancySession extends Model
{
    use BelongsToTenant;

    protected $table = 'table_occupancy_sessions';

    protected $fillable = [
        'club_id',
        'branch_id',
        'resource_id',
        'start_at',
        'end_at',
        'source',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'start_at'         => 'datetime',
            'end_at'           => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Facilities\Models\Resource::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Facilities\Models\Branch::class);
    }

    /** Длительность в минутах */
    public function durationMinutes(): float
    {
        return round($this->duration_seconds / 60, 1);
    }
}
