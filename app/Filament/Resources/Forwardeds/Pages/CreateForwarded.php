<?php

namespace App\Filament\Resources\Forwardeds\Pages;

use App\Filament\Resources\Forwardeds\ForwardedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateForwarded extends CreateRecord
{
    protected static string $resource = ForwardedResource::class;

    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
}

