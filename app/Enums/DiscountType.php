<?php

namespace App\Enums;

enum DiscountType: string
{
    case Nominal = 'nominal';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Nominal => 'Rp',
            self::Percent => '%',
        };
    }
}
