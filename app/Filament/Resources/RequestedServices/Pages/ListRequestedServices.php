<?php

namespace App\Filament\Resources\RequestedServices\Pages;

use App\Filament\Resources\RequestedServices\RequestedServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequestedServices extends ListRecords
{
    protected static string $resource = RequestedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
