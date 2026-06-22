<?php

namespace App\Support\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow    = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Ожидает',
            self::Confirmed => 'Подтверждена',
            self::Cancelled => 'Отменена',
            self::Completed => 'Завершена',
            self::NoShow    => 'Неявка',
        };
    }

    /**
     * Используется Moonshine-полем Enum для подписей опций и превью.
     */
    public function toString(): string
    {
        return $this->label();
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Cancelled, self::Completed, self::NoShow], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending   => in_array($next, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($next, [self::Completed, self::Cancelled, self::NoShow], true),
            default         => false,
        };
    }
}
