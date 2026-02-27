<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AppointmentsByTypeChart extends ChartWidget
{
    // 1. Variáveis públicas limpas para receber os dados injetados
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;

    protected ?string $heading = 'Ranking de Atendimentos por Terapia';
    protected ?string $maxHeight = '300px';

    // 2. O Receptor: Escuta o gatilho, recebe os dados soltos (com proteção null) e salva
    #[On('atualizar-relatorio')]
    public function atualizarFiltros($mes = null, $ano = null, $patient_id = null, $therapy_id = null): void
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
    }

    protected function getData(): array
    {
        // 3. Define as datas com base no filtro ou usa a data atual
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        // 4. Constrói a consulta aplicando todos os filtros do formulário
        $query = Appointment::query()
            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
            ->whereMonth('appointments.appointment_date', $mesFiltrado)
            ->whereYear('appointments.appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn (Builder $q) => $q->where('appointments.patient_id', $this->patient_id))
            ->when($this->therapy_id, fn (Builder $q) => $q->where('appointments.therapy_id', $this->therapy_id));

        // 5. Agrupa os resultados pelas terapias
        $data = $query
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
                        '#48D1CC', '#40E0D0', '#76D7C4', '#A2D9CE', 
                        '#A3E4D7', '#D1F2EB', '#E8F8F5'
                    ],
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
                    ],
                ],
                // ADICIONAMOS A REGRA DO EIXO Y AQUI:
                'y' => [
                    'ticks' => [
                        'autoSkip' => false, // <-- Obriga o gráfico a mostrar TODOS os nomes!
                    ],
                ],
            ],
        ];
    }
}