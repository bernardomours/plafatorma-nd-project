<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class AppointmentsByTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;
    protected ?string $heading = 'Ranking de Atendimentos por Terapia';

    protected function getTablePage(): string
    {
        return ListAppointments::class;
    }

    protected function getData(): array
    {
        $query = $this->getPageTableQuery();
        $data = $query
            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
            ->select('therapies.name', DB::raw('SUM(appointments.session_number) as count'))
            ->groupBy('therapies.name')
            ->orderByDesc('count')
            ->get();

        $labels = $data->pluck('name')->toArray();
        $counts = $data->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Qtd de Atendimentos',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#48D1CC',
                        '#40E0D0', 
                        '#76D7C4',
                        '#A2D9CE',
                        '#A3E4D7',
                        '#D1F2EB',
                        '#E8F8F5',
                    ],
                    'borderRadius' => 4,
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
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}