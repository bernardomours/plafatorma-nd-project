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
    protected ?string $pollingInterval = null;
    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public array $unidades = [];
    public ?string $agreement_id = null; // <-- NOVO: Variável do Convênio adicionada

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

    protected function getStats(): array
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