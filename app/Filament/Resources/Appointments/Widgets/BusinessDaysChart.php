<?php

namespace App\Filament\Resources\Appointments\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Holiday;
use Carbon\Carbon;
use App\Models\Appointment; 
use Livewire\Attributes\On;

class BusinessDaysChart extends ChartWidget
{
    protected ?string $heading = 'Comparativo: Mês Selecionado vs Mês Anterior (Dias Úteis)';
    
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = null;

    public ?string $mes = null;
    public ?string $ano = null;
    public ?array $unidades = [];
    public ?string $agreement_id = null;
    public ?string $therapy_id = null;
    public ?string $patient_id = null;

    #[On('atualizar-relatorio')]
    public function atualizaDadosDoGrafico($mes, $ano, $patient_id = null, $therapy_id = null, $unidades = [], $agreement_id = null)
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
        $this->unidades = $unidades ?: [];
        $this->agreement_id = $agreement_id;
    }

    protected function getData(): array
    {
        $mesAtual = $this->mes ? (int) $this->mes : Carbon::now()->month;
        $anoAtual = $this->ano ? (int) $this->ano : Carbon::now()->year;

        $dataSelecionada = Carbon::createFromDate($anoAtual, $mesAtual, 1);
        
        $mesPassado = $dataSelecionada->copy()->subMonth()->month;
        $anoPassado = $dataSelecionada->copy()->subMonth()->year;

        $diasUteisAtual = $this->getBusinessDays($anoAtual, $mesAtual);
        $diasUteisPassado = $this->getBusinessDays($anoPassado, $mesPassado);

        $maxDias = max(count($diasUteisAtual), count($diasUteisPassado));
        
        $labels = [];
        for ($i = 1; $i <= $maxDias; $i++) {
            $labels[] = "{$i}º Dia";
        }

        $dadosMesAtual = $this->fetchAttendanceData($diasUteisAtual);
        $dadosMesPassado = $this->fetchAttendanceData($diasUteisPassado);

        return [
            'datasets' => [
                [
                    'label' => 'Mês Atual',
                    'data' => $dadosMesAtual,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => 'Mês Anterior',
                    'data' => $dadosMesPassado,
                    'borderColor' => '#9ca3af',
                    'borderDash' => [5, 5],
                    'backgroundColor' => 'transparent',
                    'tension' => 0.4,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getBusinessDays($year, $month): array
    {
        $holidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('date')
            ->toArray();

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $businessDays = [];
        $index = 1;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            $dateString = $date->format('Y-m-d');

            if (!$date->isWeekend() && !in_array($dateString, $holidays)) {
                $businessDays[$index] = $dateString; // Ex: [1 => '2026-05-04']
                $index++;
            }
        }

        return $businessDays;
    }

    private function fetchAttendanceData(array $businessDays): array
    {
        if (empty($businessDays)) return [];

        $startDate = min($businessDays);
        $endDate = max($businessDays);

        $query = Appointment::whereBetween('appointment_date', [$startDate, $endDate]);

        if ($this->therapy_id) {
            $query->where('therapy_id', $this->therapy_id);
        }
        if ($this->patient_id) {
            $query->where('patient_id', $this->patient_id);
        }
        if (!empty($this->unidades)) {
            $query->whereHas('patient', function ($q) {
                $q->whereIn('unit_id', $this->unidades);
            });
        }
        if ($this->agreement_id) {
            $query->whereHas('patient', function ($q) {
                $q->where('agreement_id', $this->agreement_id);
            });
        }

        $attendances = $query->selectRaw('DATE(appointment_date) as date, SUM(appointments.session_number) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $dataForChart = [];
        
        $acumulado = 0;

        foreach ($businessDays as $index => $dateString) {
            $totalDoDia = $attendances[$dateString] ?? 0;
            
            $acumulado += $totalDoDia;
            
            $dataForChart[] = $acumulado;
        }

        return $dataForChart;
    }
}