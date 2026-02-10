<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListAppointments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStats::class,
        ];
    }
}
