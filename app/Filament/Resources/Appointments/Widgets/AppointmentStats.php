<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppointmentStats extends BaseWidget
{
    use InteractsWithPageTable;
    public array $tableColumnSearches = [];

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\Appointments\Pages\AttendanceReports::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery()->clone();

        $query->getQuery()->groups = null;
        $query->getQuery()->columns = null;
        $query->getQuery()->orders = null;

        $totalSessoes = (clone $query)->sum('session_number');
        $totalAppointments = (clone $query)->count(); 
        $mesFiltrado = $this->tableFilters['mes']['value'] ?? date('m');
        $ano = date('Y');

        $startDate = \Carbon\Carbon::createFromDate($ano, $mesFiltrado, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $average = ($numberOfDays > 0) ? ($totalAppointments / $numberOfDays) : 0;

        return [
            Stat::make('Total de Sessões', $totalSessoes)
                ->description('Soma das sessões no período')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            
            Stat::make('Média Diária', number_format($average, 2))
                ->description('Média de atendimentos por dia')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
            
            Stat::make(' - ', ' ')
                ->description(' ')
                ->color('gray'),
        ];
    }
}
