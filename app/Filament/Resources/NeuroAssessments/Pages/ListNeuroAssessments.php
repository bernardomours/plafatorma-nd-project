<?php

namespace App\Filament\Resources\NeuroAssessments\Pages;

use App\Filament\Resources\NeuroAssessments\NeuroAssessmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNeuroAssessments extends ListRecords
{
    protected static string $resource = NeuroAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar Avaliação'),
        ];
    }
}
