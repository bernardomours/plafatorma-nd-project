<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class AppointmentStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListAppointments::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalSessoes = $query->sum('session_number');

        return [
            Stat::make('Total de Sessões', $totalSessoes)
                ->description('Sessões realizadas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
