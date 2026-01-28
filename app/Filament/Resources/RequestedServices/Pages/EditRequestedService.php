<?php

namespace App\Filament\Resources\RequestedServices\Pages;

use App\Filament\Resources\RequestedServices\RequestedServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRequestedService extends EditRecord
{
    protected static string $resource = RequestedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
}
