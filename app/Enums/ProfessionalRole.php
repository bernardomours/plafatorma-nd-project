<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProfessionalRole: string implements HasLabel
{
    case Supervisor = 'supervisor';
    case Coordinator = 'coordinator';
    case Therapist = 'therapist';
    case Uncategorized = 'uncategorized';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Supervisor => 'Supervisor',
            self::Coordinator => 'Coordenador',
            self::Therapist => 'AT',
            self::Uncategorized => 'Não Registrado',
        };
    }
}
