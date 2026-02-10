<?php

namespace App\Filament\Resources\RequestedServices\Pages;

use App\Filament\Resources\RequestedServices\RequestedServiceResource;
use App\Filament\Resources\RequestedServices\Widgets\RequestedServiceStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListRequestedServices extends ListRecords
{
    use ExposesTableToWidgets; // <--- 2. ADICIONE ISSO AQUI DENTRO DA CLASSE

    protected static string $resource = RequestedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RequestedServiceStats::class,
        ];
    }
}
