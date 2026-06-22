<?php

namespace App\Domain\Services\Models;

use App\Support\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Правило ценообразования для услуги.
 *
 * Деньги хранятся в минорных единицах (копейках).
 * Никогда не используем float.
 *
 * Правило применяется если:
 *  - valid_from/valid_to охватывает дату
 *  - day_of_week совпадает (или null = любой)
 *  - time_from/time_to охватывает время (или null = весь день)
 */
class PricingRule extends Model
{
    use LogsActivity;

    protected $fillable = [
        'service_offering_id',
        'amount_minor',
        'currency_code',
        'valid_from',
        'valid_to',
        'day_of_week',
        'time_from',
        'time_to',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'valid_from'   => 'date',
            'valid_to'     => 'date',
            'day_of_week'  => 'integer',
            'priority'     => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    /**
     * Аудит (Правило 5): фиксируем изменения цены.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('pricing')
            ->logOnly(['amount_minor', 'currency_code', 'valid_from', 'valid_to', 'day_of_week', 'time_from', 'time_to', 'priority'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    // -------------------------------------------------------------------------
    // Money value-object
    // -------------------------------------------------------------------------

    /**
     * Получить цену как Money value-object.
     */
    public function money(): Money
    {
        return Money::of($this->amount_minor, $this->currency_code);
    }

    // -------------------------------------------------------------------------
    // Проверка применимости правила
    // -------------------------------------------------------------------------

    /**
     * Применимо ли правило к указанной дате и времени (локальному времени филиала).
     *
     * @param Carbon $localDateTime — локальное время в часовом поясе филиала
     */
    public function appliesTo(Carbon $localDateTime): bool
    {
        // Проверка периода действия
        if ($this->valid_from && $localDateTime->lt($this->valid_from->startOfDay())) {
            return false;
        }
        if ($this->valid_to && $localDateTime->gt($this->valid_to->endOfDay())) {
            return false;
        }

        // Проверка дня недели (0=Вс..6=Сб — совпадает с Carbon dayOfWeek)
        if ($this->day_of_week !== null && $localDateTime->dayOfWeek !== $this->day_of_week) {
            return false;
        }

        // Проверка диапазона времени
        if ($this->time_from !== null && $this->time_to !== null) {
            $timeStr   = $localDateTime->format('H:i:s');
            $timeFrom  = substr($this->time_from, 0, 8);
            $timeTo    = substr($this->time_to,   0, 8);
            if ($timeStr < $timeFrom || $timeStr >= $timeTo) {
                return false;
            }
        }

        return true;
    }
}
