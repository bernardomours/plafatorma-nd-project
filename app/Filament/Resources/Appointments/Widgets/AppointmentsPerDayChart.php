<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AppointmentsPerDayChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Atendimentos por Dia';
    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListAppointments::class;
    }

    protected function getData(): array
    {
        // Pega a query da tabela JÁ COM OS FILTROS APLICADOS, graças ao trait.
        $query = $this->getPageTableQuery();
        
        // CORREÇÃO: Usa a propriedade $tableFilters do Filament v3
        $filters = $this->tableFilters; 
        
        $appointmentDateFilters = $filters['appointment_date'] ?? [];

        $startDate = isset($appointmentDateFilters['date_from']) ? Carbon::parse($appointmentDateFilters['date_from']) : Carbon::now()->startOfMonth();
        $endDate = isset($appointmentDateFilters['date_until']) ? Carbon::parse($appointmentDateFilters['date_until']) : Carbon::now()->endOfMonth();

        // Agrupa e conta os atendimentos por dia usando a query já filtrada.
        $data = $query->select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
                      ->groupBy('date')
                      ->orderBy('date', 'asc')
                      ->get()
                      ->pluck('count', 'date')
                      ->toArray();

        // Preenche os dias sem atendimentos com o valor 0 para manter a consistência do gráfico.
        // Dica: Adicionei o ->copy() no endDate para evitar que a data original seja modificada acidentalmente no loop
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());
        $labels = [];
        $dataset = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $dataset[] = $data[$dateString] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Atendimentos',
                    'data' => $dataset,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false, // Esconde a legenda "Atendimentos" embaixo se você achar redundante
                ],
            ],
        ];
    }
}
