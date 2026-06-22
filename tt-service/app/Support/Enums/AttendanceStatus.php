<?php

namespace App\Support\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent  = 'absent';
    case NoShow  = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Присутствовал',
            self::Absent  => 'Отсутствовал',
            self::NoShow  => 'Неявка без предупреждения',
        };
    }
}
