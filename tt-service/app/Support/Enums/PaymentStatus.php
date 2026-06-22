<?php

namespace App\Support\Enums;

enum PaymentStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Refunded  = 'refunded';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Ожидает оплаты',
            self::Paid      => 'Оплачено',
            self::Refunded  => 'Возвращено',
            self::Failed    => 'Ошибка',
            self::Cancelled => 'Отменено',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Refunded, self::Failed, self::Cancelled]);
    }
}
