<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Livewire\Attributes\On;

class AppointmentStats extends BaseWidget
{
    // 1. Variáveis públicas que guardarão os filtros
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;

    // 2. O Receptor: Escuta o gatilho, abre o pacote e salva os dados
    #[On('atualizar-relatorio')]
    public function atualizarFiltros($mes = null, $ano = null, $patient_id = null, $therapy_id = null): void
    {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->patient_id = $patient_id;
        $this->therapy_id = $therapy_id;
    }

    // 3. O Construtor do Gráfico
    protected function getStats(): array
    {
        // Pega o mês e ano do filtro (ou o atual se estiver vazio)
        $mesFiltrado = $this->mes ?: date('m');
        $anoFiltrado = $this->ano ?: date('Y');

        // Calcula o primeiro e o último dia do mês para a média diária
        $startDate = Carbon::createFromDate($anoFiltrado, $mesFiltrado, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Montamos a consulta no banco de dados aplicando os filtros
        $query = Appointment::query()
            ->whereMonth('appointment_date', $mesFiltrado)
            ->whereYear('appointment_date', $anoFiltrado)
            ->when($this->patient_id, fn (Builder $q) => $q->where('patient_id', $this->patient_id))
            ->when($this->therapy_id, fn (Builder $q) => $q->where('therapy_id', $this->therapy_id));

        // Clonamos a consulta para extrair os números
        $totalSessoes = (clone $query)->sum('session_number');
        $totalAppointments = (clone $query)->count(); 

        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $average = ($numberOfDays > 0) ? ($totalAppointments / $numberOfDays) : 0;

        return [
            Stat::make('Total de Sessões', $totalSessoes)
                ->description('Soma das sessões no período')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            
            Stat::make('Média Diária', number_format($average, 2))
                ->description('Média de atendimentos por dia no período')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
            
            Stat::make('Total de Atendimentos', $totalAppointments)
                ->description('Quantidade de registros de atendimento')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
        ];
    }
}