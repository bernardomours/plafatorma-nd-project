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

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListAppointments::class;
    }

    protected function getStats(): array
    {
        // Pega a query da tabela JÁ COM OS FILTROS APLICADOS.
        $query = $this->getPageTableQuery();

        // 1. CALCULA O TOTAL DE ATENDIMENTOS
        $totalAppointments = $this->getPageTableQuery()->count();

        // 2. CALCULA A MÉDIA DIÁRIA
        $filters = $this->tableFilters;
        $appointmentDateFilters = $filters['appointment_date'] ?? [];

        $startDate = isset($appointmentDateFilters['date_from']) ? Carbon::parse($appointmentDateFilters['date_from']) : Carbon::now()->startOfMonth();
        $endDate = isset($appointmentDateFilters['date_until']) ? Carbon::parse($appointmentDateFilters['date_until']) : Carbon::now()->endOfMonth();

        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $average = ($numberOfDays > 0) ? ($totalAppointments / $numberOfDays) : 0;

        // 3. RETORNA OS CARDS
        return [
            Stat::make('Total de Atendimentos', $totalAppointments)
                ->description('Nº de atendimentos no período')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            
            Stat::make('Média Diária', number_format($average, 2))
                ->description('Média de atendimentos por dia')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
            
            // Card em branco, como solicitado no layout
            Stat::make('-', ' ')
                ->description(' ')
                ->color('gray'),
        ];
    }
}
