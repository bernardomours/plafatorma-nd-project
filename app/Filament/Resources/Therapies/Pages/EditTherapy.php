<?php

namespace App\Filament\Resources\Therapies\Pages;

use App\Filament\Resources\Therapies\TherapyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTherapy extends EditRecord
{
    protected static string $resource = TherapyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
