<?php

namespace App\Filament\Resources\Appointments\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Holiday; // Nosso model de feriados
use Carbon\Carbon;
use App\Models\Appointment; 
use Livewire\Attributes\On;

class BusinessDaysChart extends ChartWidget
{
    protected ?string $heading = 'Comparativo: Mês Selecionado vs Mês Anterior (Dias Úteis)';
    
    protected int | string | array $columnSpan = 'full';

    // 1. Crie estas propriedades para receber os filtros da página
    public ?string $mes = null;
    public ?string $ano = null;

    #[On('atualizar-relatorio')]
    public function atualizaDadosDoGrafico($mes, $ano)
    {
        // Atualiza as variáveis do gráfico com as que vieram do filtro
        $this->mes = $mes;
        $this->ano = $ano;
        
        // O Livewire é inteligente: assim que mudamos essas variáveis, 
        // ele roda o getData() de novo sozinho e o gráfico pisca atualizado na tela!
    }

    protected function getData(): array
    {
        // 2. Troca o "Hoje" pelas datas que vieram do filtro (se vier vazio, usa a data atual)
        $mesAtual = $this->mes ? (int) $this->mes : Carbon::now()->month;
        $anoAtual = $this->ano ? (int) $this->ano : Carbon::now()->year;

        // O Carbon nos ajuda a achar qual foi o mês passado baseado na seleção
        $dataSelecionada = Carbon::createFromDate($anoAtual, $mesAtual, 1);
        
        $mesPassado = $dataSelecionada->copy()->subMonth()->month;
        $anoPassado = $dataSelecionada->copy()->subMonth()->year;

        // 1. Gera o Gabarito de Dias Úteis dos dois meses
        $diasUteisAtual = $this->getBusinessDays($anoAtual, $mesAtual);
        $diasUteisPassado = $this->getBusinessDays($anoPassado, $mesPassado);

        // Define quantas "colunas" (Dias Úteis) o gráfico vai ter no máximo
        $maxDias = max(count($diasUteisAtual), count($diasUteisPassado));
        
        // As etiquetas que vão ficar embaixo do gráfico (1º Dia Útil, 2º Dia Útil...)
        $labels = [];
        for ($i = 1; $i <= $maxDias; $i++) {
            $labels[] = "{$i}º Dia";
        }

        // 2. Busca os dados no Banco de Dados
        $dadosMesAtual = $this->fetchAttendanceData($diasUteisAtual);
        $dadosMesPassado = $this->fetchAttendanceData($diasUteisPassado);

        // 3. Monta o Gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Mês Atual',
                    'data' => $dadosMesAtual,
                    'borderColor' => '#3b82f6', // Azul forte
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, // Deixa a linha mais curvadinha/suave
                ],
                [
                    'label' => 'Mês Anterior',
                    'data' => $dadosMesPassado,
                    'borderColor' => '#9ca3af', // Cinza
                    'borderDash' => [5, 5], // Linha pontilhada para o passado
                    'backgroundColor' => 'transparent',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * FUNÇÃO INTELIGENTE 1: Calcula os dias úteis pulando finais de semana e feriados
     */
    private function getBusinessDays($year, $month): array
    {
        // Busca os feriados daquele mês/ano específico
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

            // Se NÃO for final de semana E NÃO estiver na tabela de feriados...
            if (!$date->isWeekend() && !in_array($dateString, $holidays)) {
                $businessDays[$index] = $dateString; // Ex: [1 => '2026-05-04']
                $index++;
            }
        }

        return $businessDays;
    }

    /**
     * FUNÇÃO INTELIGENTE 2: Conta os atendimentos para cada dia útil do gabarito
     */
    private function fetchAttendanceData(array $businessDays): array
    {
        if (empty($businessDays)) return [];

        $startDate = min($businessDays);
        $endDate = max($businessDays);

        $attendances = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->selectRaw('DATE(appointment_date) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $dataForChart = [];

        // Para cada dia útil (1, 2, 3...), pega o total. Se não teve nenhum, põe 0.
        foreach ($businessDays as $index => $dateString) {
            $dataForChart[] = $attendances[$dateString] ?? 0;
        }

        return $dataForChart;
    }
}