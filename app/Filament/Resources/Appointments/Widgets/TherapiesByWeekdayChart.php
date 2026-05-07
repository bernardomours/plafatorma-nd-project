<?php

namespace App\Filament\Resources\Appointments\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Attributes\On;

class TherapiesByWeekdayChart extends ChartWidget
{
    protected ?string $heading = 'Terapias por Dia da Semana (Mês Selecionado)';
    
    protected ?string $pollingInterval = null;  
    protected int | string | array $columnSpan = 'full';

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

        $query = Appointment::with('therapy')
            ->whereMonth('appointment_date', $mesAtual)
            ->whereYear('appointment_date', $anoAtual);

        if ($this->therapy_id) $query->where('therapy_id', $this->therapy_id);
        if ($this->patient_id) $query->where('patient_id', $this->patient_id);
        if (!empty($this->unidades)) {
            $query->whereHas('patient', fn($q) => $q->whereIn('unit_id', $this->unidades));
        }
        if ($this->agreement_id) {
            $query->whereHas('patient', fn($q) => $q->where('agreement_id', $this->agreement_id));
        }

        $atendimentos = $query->get(['id', 'appointment_date', 'therapy_id', 'session_number']);

        $diasDaSemana = [
            1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 
            4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'
        ];

        $terapias = $atendimentos->groupBy(function ($item) {
            return $item->therapy ? $item->therapy->name : 'Sem Terapia';
        });

        $datasets = [];
        $cores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];
        $indexCor = 0;

        foreach ($terapias as $nomeTerapia => $itens) {
            $dadosDaTerapia = [];
            
            foreach ($diasDaSemana as $diaId => $nomeDia) {
                $quantidade = $itens->filter(function($item) use ($diaId) {
                    return Carbon::parse($item->appointment_date)->dayOfWeek === $diaId;
                })->sum('session_number');
                
                $dadosDaTerapia[] = $quantidade;
            }

            $datasets[] = [
                'label' => $nomeTerapia,
                'data' => $dadosDaTerapia,
                'backgroundColor' => $cores[$indexCor % count($cores)],
            ];
            $indexCor++;
        }

        return [
            'datasets' => $datasets,
            'labels' => array_values($diasDaSemana),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}