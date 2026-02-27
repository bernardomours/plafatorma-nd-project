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
    // 1. As variáveis públicas limpas para receber os filtros
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    
    protected ?string $heading = 'Atendimentos por Dia';
    protected ?string $maxHeight = '300px';

    // 2. O Receptor: Escuta o gatilho, recebe os dados com proteção null e salva
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
        // 3. Define o mês e ano filtrados ou usa o atual
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        // 4. Calcula o primeiro e último dia do mês filtrado para desenhar o eixo X
        $startDate = Carbon::createFromDate($anoFiltrado, $mesFiltrado, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 5. Cria a query base aplicando TODOS os filtros
        $query = Appointment::query()
            ->whereMonth('appointment_date', $mesFiltrado)
            ->whereYear('appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn (Builder $q) => $q->where('patient_id', $this->patient_id))
            ->when($this->therapy_id, fn (Builder $q) => $q->where('therapy_id', $this->therapy_id));

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // 6. Busca os dados somando as sessões por DIA
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

        // 7. Preenche o gráfico, colocando 0 nos dias que não tiveram atendimento
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
                        'autoSkip' => false, // <-- Obriga a mostrar TODOS os dias
                        'maxRotation' => 45, // <-- Inclina o texto em 45 graus para caber
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