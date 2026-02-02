<?php

namespace App\Filament\Resources\Forwardeds\Pages;

use App\Filament\Resources\Forwardeds\ForwardedResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditForwarded extends EditRecord
{
    protected static string $resource = ForwardedResource::class;

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
