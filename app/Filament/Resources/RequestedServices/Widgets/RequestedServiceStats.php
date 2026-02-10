<?php

namespace App\Filament\Resources\RequestedServices\Widgets;

use App\Filament\Resources\RequestedServices\Pages\ListRequestedServices;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RequestedServiceStats extends BaseWidget
{
    use InteractsWithPageTable;

    public array $tableColumnSearches = [];

    protected function getTablePage(): string
    {
        return ListRequestedServices::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        return [
            Stat::make('Total de Horas Solicitadas', $query->clone()->sum('requested_hours'))
                ->description('Soma das horas solicitadas')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('warning'),
            Stat::make('Total de Horas Liberadas', $query->clone()->sum('approved_hours'))
                ->description('Soma das horas aprovadas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Total de Horas Planejadas', $query->clone()->sum('planned_hours'))
                ->description('Soma das horas planejadas')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
