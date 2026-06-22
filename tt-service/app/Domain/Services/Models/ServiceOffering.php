<?php

namespace App\Domain\Services\Models;

use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\ServiceOfferingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

/**
 * Услуга — то, что бронирует клиент.
 *
 * Примеры: аренда стола, индивидуальная тренировка,
 * групповая тренировка, аренда зала целиком.
 *
 * Цена хранится в PricingRule (гибкая: по дню/времени).
 * Требования к ресурсам — в ServiceResourceRequirement.
 */
class ServiceOffering extends Model
{
    /** @use HasFactory<ServiceOfferingFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'name',
        'description',
        'duration_minutes',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'capacity'         => 'integer',
            'is_active'        => 'boolean',
        ];
    }

    protected static function newFactory(): ServiceOfferingFactory
    {
        return ServiceOfferingFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ServiceOffering $service) {
            if (empty($service->public_id)) {
                $service->public_id = strtolower((string) new Ulid());
            }
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function resourceRequirements(): HasMany
    {
        return $this->hasMany(ServiceResourceRequirement::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class)->orderByDesc('priority');
    }
}
