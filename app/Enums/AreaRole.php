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
            self::Admin => 'Administrador',
            self::Guard => 'Guardia',
            self::Contact => 'Contacto',
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
