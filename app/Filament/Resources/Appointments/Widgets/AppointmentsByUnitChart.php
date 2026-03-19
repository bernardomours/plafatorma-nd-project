<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AppointmentsByUnitChart extends ChartWidget
{
    // Trava para não ficar atualizando sozinho e dar aquele erro GET
    protected ?string $pollingInterval = null;
    
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public array $unidades = [];
    public ?string $agreement_id = null;

    protected ?string $heading = 'Ranking de Atendimentos por Unidade';
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

        $query = Appointment::query()
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->join('units', 'patients.unit_id', '=', 'units.id')
            ->whereMonth('appointments.appointment_date', $mesFiltrado)
            ->whereYear('appointments.appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn ($q) => $q->where('appointments.patient_id', $this->patient_id))
            ->when($this->therapy_id, fn ($q) => $q->where('appointments.therapy_id', $this->therapy_id))
            ->when(!empty($this->unidades), fn ($q) => $q->whereIn('patients.unit_id', $this->unidades))
            ->when($this->agreement_id, fn ($q) => $q->where('patients.agreement_id', $this->agreement_id));

        $data = $query
            ->select('units.city as unit_name', DB::raw('SUM(appointments.session_number) as count'))
            ->groupBy('units.city')
            ->orderByDesc('count')
            ->get();

        $labels = $data->pluck('unit_name')->toArray();
        $counts = $data->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Qtd de Atendimentos',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#3B82F6'
                    ], // Paleta de cores moderna pra combinar com os outros
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
            'indexAxis' => 'y', // Deixa o gráfico deitado igual o de terapias
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
            ],
        ];
    }
}