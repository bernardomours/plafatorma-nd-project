<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VisitStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Completed => 'Realizada',
            self::Canceled => 'Cancelada',
        };
    }
}
