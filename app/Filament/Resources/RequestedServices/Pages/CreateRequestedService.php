<?php

namespace App\Filament\Resources\RequestedServices\Pages;

use App\Filament\Resources\RequestedServices\RequestedServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestedService extends CreateRecord
{
    protected static string $resource = RequestedServiceResource::class;

    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
}
