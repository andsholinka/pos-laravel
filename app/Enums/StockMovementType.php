<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Barang Masuk',
            self::Adjustment => 'Penyesuaian',
            self::Sale => 'Penjualan',
            self::Void => 'Pembatalan',
        };
    }

    /** Badge color key (see x-badge). */
    public function color(): string
    {
        return match ($this) {
            self::In => 'green',
            self::Adjustment => 'amber',
            self::Sale => 'blue',
            self::Void => 'slate',
        };
    }

    /** Icon name for x-icon. */
    public function icon(): string
    {
        return match ($this) {
            self::In => 'arrow-down-tray',
            self::Adjustment => 'adjustments-horizontal',
            self::Sale => 'shopping-cart',
            self::Void => 'no-symbol',
        };
    }
}
