<?php

namespace App\Domain\Services\Models;

use App\Domain\Facilities\Models\ResourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Требование услуги к ресурсам.
 *
 * Описывает: «для этой услуги нужно N ресурсов типа X».
 * AvailabilityService использует это чтобы знать, какие ресурсы
 * искать при поиске свободных слотов.
 */
class ServiceResourceRequirement extends Model
{
    protected $fillable = [
        'service_offering_id',
        'resource_type_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }
}
