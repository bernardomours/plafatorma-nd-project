<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
}



