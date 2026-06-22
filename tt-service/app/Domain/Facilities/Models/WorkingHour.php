<?php

namespace App\Domain\Facilities\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Рабочее время филиала по дням недели.
 *
 * day_of_week: 0 = Вс, 1 = Пн, 2 = Вт, 3 = Ср, 4 = Чт, 5 = Пт, 6 = Сб.
 * open_time / close_time хранятся в локальном времени филиала (time без tz),
 * потому что «открыт с 09:00 до 22:00» — это локальное понятие.
 * При поиске доступности перевод в UTC делается через timezone филиала.
 */
class WorkingHour extends Model
{
    protected $fillable = [
        'branch_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed'   => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Человекочитаемое название дня.
     */
    public function dayName(): string
    {
        return match ($this->day_of_week) {
            0 => 'Воскресенье',
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
        };
    }
}
