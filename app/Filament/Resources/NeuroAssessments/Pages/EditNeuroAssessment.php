<?php

namespace App\Filament\Resources\NeuroAssessments\Pages;

use App\Filament\Resources\NeuroAssessments\NeuroAssessmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNeuroAssessment extends EditRecord
{
    protected static string $resource = NeuroAssessmentResource::class;

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
