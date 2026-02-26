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
    public array $tableColumnSearches = [];

    protected ?string $heading = 'Atendimentos por Dia';
    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\Appointments\Pages\AttendanceReports::class;
    }

    protected function getData(): array
    {
        $query = $this->getPageTableQuery()->clone();

        $query->getQuery()->groups = null;
        $query->getQuery()->columns = null;
        $query->getQuery()->orders = null;

        $mesFiltrado = $this->tableFilters['mes']['value'] ?? date('m');
        $ano = date('Y'); 

        $startDate = \Carbon\Carbon::createFromDate($ano, $mesFiltrado, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';

        // Busca os dados somando as sessões por DIA
        $dadosBanco = $query->select(
            $isSqlite 
                ? \Illuminate\Support\Facades\DB::raw("strftime('%d', appointment_date) as dia")
                : \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(appointment_date, '%d') as dia"),
            \Illuminate\Support\Facades\DB::raw('SUM(session_number) as total')
        )
        ->groupBy('dia')
        ->pluck('total', 'dia')
        ->toArray();

        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());
        
        $labels = [];
        $dataset = [];

        foreach ($period as $date) {
            $diaPad = $date->format('d'); // Retorna '01', '02', etc.
            $labels[] = $date->format('d/m'); // Exibe '01/02' no gráfico
            
            // Puxa o total do banco ou coloca 0 se não teve atendimento
            $dataset[] = $dadosBanco[$diaPad] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sessões Realizadas',
                    'data' => $dataset,
                    'backgroundColor' => '#48D1CC',
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
