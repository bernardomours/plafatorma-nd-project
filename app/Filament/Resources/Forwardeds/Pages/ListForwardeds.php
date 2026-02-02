<?php

namespace App\Filament\Resources\Forwardeds\Pages;

use App\Filament\Resources\Forwardeds\ForwardedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListForwardeds extends ListRecords
{
    protected static string $resource = ForwardedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
