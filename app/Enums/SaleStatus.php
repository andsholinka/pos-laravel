<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Void => 'Dibatalkan',
        };
    }

    /** Badge color key (see x-badge). */
    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Void => 'red',
        };
    }
}
