<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AppointmentsByTypeChart extends ChartWidget
{
    protected ?string $pollingInterval = null;
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public array $unidades = [];

    protected ?string $heading = 'Ranking de Atendimentos por Terapia';
    protected ?string $maxHeight = '300px';

    #[On('atualizar-relatorio')]
    public function atualizarFiltros($mes = null, $ano = null, $patient_id = null, $therapy_id = null, $unidades = []): void // <-- Parâmetro adicionado
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
        $this->unidades = $unidades;
    }

    protected function getData(): array
    {
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        $query = Appointment::query()
            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
            ->whereMonth('appointments.appointment_date', $mesFiltrado)
            ->whereYear('appointments.appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn (Builder $q) => $q->where('appointments.patient_id', $this->patient_id))
            ->when($this->therapy_id, fn (Builder $q) => $q->where('appointments.therapy_id', $this->therapy_id))
            ->when(!empty($this->unidades), fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->whereIn('unit_id', $this->unidades)));

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
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
                ],
            ],
        ];
    }
}