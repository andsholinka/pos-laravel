<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Tunai = 'tunai';
    case Qris = 'qris';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Qris => 'QRIS',
            self::Transfer => 'Transfer',
        };
    }

    /** Badge color key (see x-badge). */
    public function color(): string
    {
        return match ($this) {
            self::Tunai => 'green',
            self::Qris => 'violet',
            self::Transfer => 'sky',
        };
    }

    /** Icon name for x-icon. */
    public function icon(): string
    {
        return match ($this) {
            self::Tunai => 'banknotes',
            self::Qris => 'qr-code',
            self::Transfer => 'building-library',
        };
    }
}
