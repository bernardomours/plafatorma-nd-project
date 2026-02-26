<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use App\Filament\Resources\Appointments\Widgets\AppointmentsByTypeChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentsPerDayChart;
use App\Filament\Resources\Appointments\Widgets\MonthlySummaryTable;
use Filament\Resources\Pages\Page;

class AttendanceReports extends Page
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.resources.appointments.pages.attendance-reports';

    protected static ?string $title = 'Relatórios de Atendimento';


    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStats::class,
            AppointmentsPerDayChart::class,
            AppointmentsByTypeChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MonthlySummaryTable::class,
        ];
    }
}
