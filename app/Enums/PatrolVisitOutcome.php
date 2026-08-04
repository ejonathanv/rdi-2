<?php

namespace App\Enums;

enum PatrolVisitOutcome: string
{
    case Questionnaire = 'questionnaire';
    case AllClear = 'all_clear';

    public function label(): string
    {
        return match ($this) {
            self::Questionnaire => 'Cuestionario',
            self::AllClear => 'Área sin novedad',
        };
    }
}
