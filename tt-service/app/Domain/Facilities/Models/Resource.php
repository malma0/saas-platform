<?php

namespace App\Domain\Facilities\Models;

use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

/**
 * Универсальная модель ресурса.
 *
 * Ресурс — всё, что можно занять во времени:
 * стол, зал, тренер, комната, «весь филиал».
 *
 * Иерархия поддерживается через таблицу resource_closure.
 * Бронь на родителя → блокирует детей, и наоборот.
 *
 * ВАЖНО: никогда не используй delete() напрямую на ресурс с детьми
 * без предварительного удаления closure-записей (делается в Action).
 */
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'branch_id',
        'resource_type_id',
        'parent_id',
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

    protected static function newFactory(): ResourceFactory
    {
        return ResourceFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Resource $resource) {
            if (empty($resource->public_id)) {
                $resource->public_id = strtolower((string) new Ulid());
            }
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Resource::class, 'parent_id');
    }

    // -------------------------------------------------------------------------
    // Closure-table: ключевой метод для конфликт-чека
    // -------------------------------------------------------------------------

    /**
     * Возвращает ID всех ресурсов, которые нужно проверить на конфликт:
     *   - сам ресурс
     *   - все его ПРЕДКИ (бронь предка блокирует нас)
     *   - все его ПОТОМКИ (наша бронь блокирует их)
     *
     * Используется в AvailabilityService и CreateBooking.
     * Один SQL-запрос — без рекурсии, именно для этого closure-таблица.
     *
     * @return array<int> список ID (включая $this->id)
     */
    public function relatedResourceIds(): array
    {
        $id = $this->id;

        $rows = DB::select(
            'SELECT DISTINCT ancestor_id AS rid FROM resource_closure WHERE descendant_id = ?
             UNION
             SELECT DISTINCT descendant_id AS rid FROM resource_closure WHERE ancestor_id = ?',
            [$id, $id]
        );

        // Сам ресурс — всегда в списке, даже если closure-записи отсутствуют
        // (ресурс создан в обход CreateResource). Иначе конфликт-чек
        // молча пропускается и появляется двойная бронь.
        return array_values(array_unique(array_merge([$id], array_column($rows, 'rid'))));
    }

    /**
     * Только потомки (не включая себя).
     *
     * @return array<int>
     */
    public function descendantIds(): array
    {
        return DB::table('resource_closure')
            ->where('ancestor_id', $this->id)
            ->where('depth', '>', 0)
            ->pluck('descendant_id')
            ->all();
    }

    /**
     * Только предки (не включая себя).
     *
     * @return array<int>
     */
    public function ancestorIds(): array
    {
        return DB::table('resource_closure')
            ->where('descendant_id', $this->id)
            ->where('depth', '>', 0)
            ->pluck('ancestor_id')
            ->all();
    }
}
