<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Nueva = 'nueva';
    case EnAtencion = 'en_atencion';
    case Resuelta = 'resuelta';
    case Descartada = 'descartada';

    public function label(): string
    {
        return match ($this) {
            self::Nueva => 'Nueva',
            self::EnAtencion => 'En atención',
            self::Resuelta => 'Resuelta',
            self::Descartada => 'Descartada',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Nueva || $this === self::EnAtencion;
    }

    public function isTerminal(): bool
    {
        return $this === self::Resuelta || $this === self::Descartada;
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Nueva => [self::EnAtencion, self::Resuelta, self::Descartada],
            self::EnAtencion => [self::Resuelta, self::Descartada],
            self::Resuelta, self::Descartada => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
