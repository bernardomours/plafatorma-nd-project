<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VisitType: string implements HasLabel
{
    case Supervision = 'supervision';
    case Coordination = 'coordination';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Supervision => 'Supervisão',
            self::Coordination => 'Coordenação',
        };
    }
}
