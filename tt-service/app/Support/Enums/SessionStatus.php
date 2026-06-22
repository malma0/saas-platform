<?php

namespace App\Support\Enums;

enum SessionStatus: string
{
    case Scheduled  = 'scheduled';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled  => 'Запланировано',
            self::InProgress => 'Идёт',
            self::Completed  => 'Завершено',
            self::Cancelled  => 'Отменено',
        };
    }
}
