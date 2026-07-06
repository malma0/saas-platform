<?php

namespace App\Domain\Facilities\Models;

use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

/**
 * Тренер клуба.
 *
 * Богатые данные (специализация, опыт, разряд, ставка) живут здесь, а
 * бронируемость обеспечивает связанный ресурс типа «тренер» (resource_id).
 * Запись на тренировку = обычная бронь этого ресурса.
 */
class Coach extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'branch_id',
        'resource_id',
        'name',
        'specialization',
        'experience_years',
        'rank',
        'bio',
        'hourly_rate_minor',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'experience_years'  => 'integer',
            'hourly_rate_minor' => 'integer',
            'is_active'         => 'boolean',
            'sort_order'        => 'integer',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Coach $coach) {
            if (empty($coach->public_id)) {
                $coach->public_id = strtolower((string) new Ulid());
            }
        });

        // Автосоздание «бэкенд»-ресурса (тип «тренер»), если он не задан —
        // так тренер становится бронируемым (конфликт-чек), в т.ч. при
        // добавлении из админки, где resource_id не указывают руками.
        static::created(function (Coach $coach) {
            if ($coach->resource_id) {
                return;
            }
            $type = ResourceType::firstOrCreate(
                ['club_id' => $coach->club_id, 'slug' => 'coach'],
                ['name' => 'Тренер']
            );
            $resource = (new CreateResource())->handle(new CreateResourceDTO(
                clubId:         $coach->club_id,
                branchId:       $coach->branch_id,
                resourceTypeId: $type->id,
                name:           $coach->name,
                parentId:       null,
                capacity:       1,
            ));
            $coach->resource_id = $resource->id;
            $coach->saveQuietly();
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // -------------------------------------------------------------------------
    // Вычисляемые атрибуты
    // -------------------------------------------------------------------------

    /** Инициалы для аватара-заглушки (напр. «ИП»). */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($first . $last);
    }

    /** Ставка за час в рублях. */
    public function hourlyRubles(): int
    {
        return (int) round($this->hourly_rate_minor / 100);
    }

    /** Виртуальное поле для админки: ставка в рублях (хранится в копейках). */
    public function getHourlyRateRubAttribute(): int
    {
        return $this->hourlyRubles();
    }

    public function setHourlyRateRubAttribute(mixed $value): void
    {
        $this->hourly_rate_minor = (int) round(((float) $value) * 100);
    }
}
