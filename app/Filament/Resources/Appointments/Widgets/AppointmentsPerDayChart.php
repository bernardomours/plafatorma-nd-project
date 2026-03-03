<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DatePeriod;
use DateInterval;
use Livewire\Attributes\On;

class AppointmentsPerDayChart extends ChartWidget
{
    protected ?string $pollingInterval = null;
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public array $unidades = [];
    public ?string $agreement_id = null; // <-- NOVO: Variável do Convênio adicionada
    
    protected ?string $heading = 'Sessões por Dia';
    protected ?string $maxHeight = '300px';

    #[On('atualizar-relatorio')]
    // <-- NOVO: Parâmetro $agreement_id adicionado
    public function atualizarFiltros($mes = null, $ano = null, $patient_id = null, $therapy_id = null, $unidades = [], $agreement_id = null): void 
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
        $this->unidades = $unidades;
        $this->agreement_id = $agreement_id; // <-- Salvando o valor recebido
    }
    
    protected function getData(): array
    {
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        $startDate = Carbon::createFromDate($anoFiltrado, $mesFiltrado, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Appointment::query()
            ->whereMonth('appointment_date', $mesFiltrado)
            ->whereYear('appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn (Builder $q) => $q->where('patient_id', $this->patient_id))
            ->when($this->therapy_id, fn (Builder $q) => $q->where('therapy_id', $this->therapy_id))
            ->when(!empty($this->unidades), fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->whereIn('unit_id', $this->unidades)))
            // <-- NOVO: Aplica o filtro de convênio no paciente
            ->when($this->agreement_id, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->where('agreement_id', $this->agreement_id)));

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $dadosBanco = $query->select(
            $isSqlite 
                ? DB::raw("strftime('%d/%m', appointment_date) as dia")
                : DB::raw("DATE_FORMAT(appointment_date, '%d/%m') as dia"),
            DB::raw('SUM(session_number) as total')
        )
        ->groupBy('dia')
        ->pluck('total', 'dia')
        ->toArray();
        
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->copy()->addDay());
        
        $labels = [];
        $dataset = [];

        foreach ($period as $date) {
            $diaFormatado = $date->format('d/m');
            $labels[] = $diaFormatado;
            $dataset[] = $dadosBanco[$diaFormatado] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sessões Realizadas',
                    'data' => $dataset,
                    'backgroundColor' => '#48D1CC',
                    'borderColor' => '#3DAAA4',
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
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1, 
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}