<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Kasir => 'Kasir',
        };
    }

    /** Badge color key (see x-badge). */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'violet',
            self::Kasir => 'sky',
        };
    }
}
