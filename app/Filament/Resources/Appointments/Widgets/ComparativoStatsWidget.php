<?php

namespace App\Filament\Resources\Appointments\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Attributes\On;

class ComparativoStatsWidget extends BaseWidget
{
    public ?string $mes = null;
    public ?string $ano = null;
    public ?array $unidades = [];
    public ?string $agreement_id = null;
    public ?string $therapy_id = null;
    public ?string $patient_id = null;

    protected ?string $pollingInterval = null;

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

    protected function getStats(): array
    {
        $mesAtual = $this->mes ? (int) $this->mes : Carbon::now()->month;
        $anoAtual = $this->ano ? (int) $this->ano : Carbon::now()->year;

        $dataSelecionada = Carbon::createFromDate($anoAtual, $mesAtual, 1);
        $mesPassado = $dataSelecionada->copy()->subMonth()->month;
        $anoPassado = $dataSelecionada->copy()->subMonth()->year;

        $dadosAtual = $this->calcularEstatisticas($mesAtual, $anoAtual);
        $dadosPassado = $this->calcularEstatisticas($mesPassado, $anoPassado);

        return [
            Stat::make('Dia + Movimentado (Mês Selecionado)', $dadosAtual['dia_nome'])
                ->description($dadosAtual['total'] . ' atendimentos no total')
                ->color('success')
                ->icon('heroicon-m-calendar-days'),

            Stat::make('Dia + Movimentado (Mês Anterior)', $dadosPassado['dia_nome'])
                ->description($dadosPassado['total'] . ' atendimentos no total')
                ->color('gray')
                ->icon('heroicon-m-calendar-days'),

            Stat::make('Média Diária', $dadosAtual['media'] . ' /dia')
                ->description('Média por dia da semana')
                ->color('info')
                ->icon('heroicon-m-calculator'),
        ];
    }

    private function calcularEstatisticas($mes, $ano)
    {
        $query = Appointment::whereMonth('appointment_date', $mes)->whereYear('appointment_date', $ano);

        if ($this->therapy_id) $query->where('therapy_id', $this->therapy_id);
        if ($this->patient_id) $query->where('patient_id', $this->patient_id);
        if (!empty($this->unidades)) {
            $query->whereHas('patient', fn($q) => $q->whereIn('unit_id', $this->unidades));
        }
        if ($this->agreement_id) {
            $query->whereHas('patient', fn($q) => $q->where('agreement_id', $this->agreement_id));
        }

        $atendimentos = $query->get(['id', 'appointment_date', 'session_number']);

        if ($atendimentos->isEmpty()) {
            return ['dia_nome' => 'Sem dados', 'total' => 0, 'media' => 0];
        }

        $agrupadoPorDiaDaSemana = $atendimentos->groupBy(function ($item) {
            return Carbon::parse($item->appointment_date)->dayOfWeek;
        });

        $contagemPorDia = $agrupadoPorDiaDaSemana->map->sum('session_number');

        $diaCampeaoId = $contagemPorDia->sortDesc()->keys()->first();
        $totalCampeao = $contagemPorDia->sortDesc()->first();

        $nomesDias = [
            0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 
            3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'
        ];

        $diasUnicos = $atendimentos->pluck('appointment_date')->unique()->count();
        $totalSessoesGeral = $atendimentos->sum('session_number');
        $media = $diasUnicos > 0 ? round($totalSessoesGeral / $diasUnicos, 1) : 0;

        return [
            'dia_nome' => $nomesDias[$diaCampeaoId] ?? '-',
            'total' => $totalCampeao,
            'media' => $media,
        ];
    }
}