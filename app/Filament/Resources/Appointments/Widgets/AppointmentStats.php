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

        // --- Card 1: Total de Sessões --- 
        // Soma o valor da coluna 'session_number' dos atendimentos filtrados.
        $totalSessoes = (clone $query)->sum('session_number');

        // --- Card 2: Média de Atendimentos por Dia ---
        $totalAppointments = (clone $query)->count(); // Conta o número de registros de atendimento
        
        $filters = $this->tableFilters;
        $appointmentDateFilters = $filters['appointment_date'] ?? [];

        $startDate = isset($appointmentDateFilters['date_from']) ? Carbon::parse($appointmentDateFilters['date_from']) : Carbon::now()->startOfMonth();
        $endDate = isset($appointmentDateFilters['date_until']) ? Carbon::parse($appointmentDateFilters['date_until']) : Carbon::now()->endOfMonth();

        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $average = ($numberOfDays > 0) ? ($totalAppointments / $numberOfDays) : 0;

        // --- Retorna os Cards ---
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
