<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AppointmentsByAgreementChart extends ChartWidget
{
    protected ?string $pollingInterval = null;
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public array $unidades = [];
    public ?string $agreement_id = null;

    protected ?string $heading = 'Ranking de Atendimentos por Convênio';
    protected ?string $maxHeight = '300px';

    #[On('atualizar-relatorio')]
    public function atualizarFiltros($mes = null, $ano = null, $patient_id = null, $therapy_id = null, $unidades = [], $agreement_id = null): void 
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
        $this->unidades = $unidades;
        $this->agreement_id = $agreement_id;
    }

    protected function getData(): array
    {
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        // Para pegar o nome do convênio, precisamos fazer JOIN com pacientes e depois com agreements
        $query = Appointment::query()
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->join('agreements', 'patients.agreement_id', '=', 'agreements.id')
            ->whereMonth('appointments.appointment_date', $mesFiltrado)
            ->whereYear('appointments.appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn ($q) => $q->where('appointments.patient_id', $this->patient_id))
            ->when($this->therapy_id, fn ($q) => $q->where('appointments.therapy_id', $this->therapy_id))
            ->when(!empty($this->unidades), fn ($q) => $q->whereIn('patients.unit_id', $this->unidades))
            ->when($this->agreement_id, fn ($q) => $q->where('patients.agreement_id', $this->agreement_id));

        $data = $query
            ->select('agreements.name', DB::raw('SUM(appointments.session_number) as count'))
            ->groupBy('agreements.name')
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
                        '#FF9F40', '#FFCD56', '#4BC0C0', '#36A2EB', 
                        '#9966FF', '#FF6384', '#C9CBCF'
                    ], // Cores diferentes para destacar do gráfico de terapias
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Gráfico de barras
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Deixa as barras deitadas (horizontal)
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