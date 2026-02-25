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
        // 1. Pega a query da tabela JÁ COM OS FILTROS APLICADOS.
        $query = $this->getPageTableQuery();

        // 2. Agrupa, conta e ORDENA do maior para o menor (formando o ranking)
        $data = $query
            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
            ->select('therapies.name', DB::raw('count(*) as count'))
            ->groupBy('therapies.name')
            ->orderByDesc('count') // <-- A mágica do ranking acontece aqui!
            ->get();

        $labels = $data->pluck('name')->toArray();
        $counts = $data->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Qtd de Atendimentos',
                    'data' => $counts,
                    // 3. Paleta de cores baseada na sua imagem de referência (tons de ciano)
                    'backgroundColor' => [
                        '#48D1CC', // Mais escuro no topo
                        '#40E0D0', 
                        '#76D7C4',
                        '#A2D9CE',
                        '#A3E4D7',
                        '#D1F2EB',
                        '#E8F8F5', // Mais clarinho no final
                    ],
                    'borderRadius' => 4, // Deixa as pontinhas das barras arredondadas
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // 4. Muda de 'doughnut' para 'bar'
        return 'bar';
    }

    protected function getOptions(): array
    {
        // 5. Configurações visuais do Chart.js para "deitar" o gráfico
        return [
            'indexAxis' => 'y', // Isso transforma as barras verticais em horizontais
            'plugins' => [
                'legend' => [
                    'display' => false, // Esconde a legenda para ficar limpo igual à imagem
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1, // Força números inteiros (1, 2, 3...)
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false, // Remove as linhas de grade horizontais de fundo
                    ],
                ],
            ],
        ];
    }
}