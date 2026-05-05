<?php

namespace App\Filament\Resources\NeuroAssessments\Pages;

use App\Filament\Resources\NeuroAssessments\NeuroAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNeuroAssessment extends CreateRecord
{
    protected static string $resource = NeuroAssessmentResource::class;

    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
}
