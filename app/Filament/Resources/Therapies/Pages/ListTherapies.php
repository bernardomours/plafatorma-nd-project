<?php

namespace App\Filament\Resources\Therapies\Pages;

use App\Filament\Resources\Therapies\TherapyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTherapies extends ListRecords
{
    protected static string $resource = TherapyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
