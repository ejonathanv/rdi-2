<?php

namespace App\Enums;

enum AreaRole: string
{
    case Admin = 'admin';
    case Guard = 'guard';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guard => 'Guard',
            self::Contact => 'Contact',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
