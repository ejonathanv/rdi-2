<?php

namespace App\Enums;

enum PatrolRunStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'En curso',
            self::Completed => 'Finalizado',
        };
    }
}
