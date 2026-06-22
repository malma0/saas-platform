<?php

namespace App\Domain\Facilities\Models;

use App\Domain\ClubCore\Models\Club;
use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

/**
 * Филиал клуба.
 *
 * У каждого филиала свой часовой пояс (IANA).
 * Рабочее время задаётся через WorkingHour по дням недели.
 * Всё время в БД — UTC; конвертация при отображении через $branch->timezone.
 */
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'name',
        'address',
        'phone',
        'email',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted(); // важно: вызываем parent для BelongsToTenant

        static::creating(function (Branch $branch) {
            if (empty($branch->public_id)) {
                $branch->public_id = strtolower((string) new Ulid());
            }
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class)->orderBy('day_of_week');
    }

    // -------------------------------------------------------------------------
    // Бизнес-логика
    // -------------------------------------------------------------------------

    /**
     * Получить рабочее время на конкретный день недели (0=Вс, 1=Пн … 6=Сб).
     */
    public function getWorkingHourForDay(int $dayOfWeek): ?WorkingHour
    {
        return $this->workingHours->firstWhere('day_of_week', $dayOfWeek);
    }

    /**
     * Открыт ли филиал в указанный день.
     */
    public function isOpenOn(int $dayOfWeek): bool
    {
        $wh = $this->getWorkingHourForDay($dayOfWeek);

        return $wh !== null && ! $wh->is_closed;
    }
}
