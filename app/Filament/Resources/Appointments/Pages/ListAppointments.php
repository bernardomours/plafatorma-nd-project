<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentsByTypeChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentsPerDayChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

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
            AppointmentsPerDayChart::class,
            AppointmentsByTypeChart::class,
        ];
    }
}
