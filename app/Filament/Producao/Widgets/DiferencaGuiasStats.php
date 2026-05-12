<?php

namespace App\Filament\Producao\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\UnpresentedGuide;
use App\Models\Appointment;

class DiferencaGuiasStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full'; 
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();

        // 1. BASE: Todas as guias importadas do CSV
        $queryTodas = UnpresentedGuide::query();

        // Aplica a trava de unidade para a base total também
        if (!$user->isAdmin()) {
            $unidadesDoUsuario = $user->units->pluck('id')->toArray();
            $queryTodas->where(function ($q) use ($unidadesDoUsuario) {
                $q->whereHas('patient', function ($queryPaciente) use ($unidadesDoUsuario) {
                    $queryPaciente->whereIn('unit_id', $unidadesDoUsuario);
                })->orWhereNull('patient_id');
            });
        }

        $totalImportadas = $queryTodas->count();

        // 2. PENDENTES: As que não estão na rastreabilidade
        $queryPendentes = (clone $queryTodas)->whereNotIn('guide', function ($q) {
            $q->select('guide')
              ->from('appointments')
              ->whereNotNull('guide');
        });

        $totalGuiasPendentes = $queryPendentes->count();
        $totalBeneficiarios = (clone $queryPendentes)->distinct('patient_name')->count('patient_name');

        // 3. CONCILIADAS: A matemática do sucesso
        $guiasConciliadas = $totalImportadas - $totalGuiasPendentes;
        $taxaSucesso = $totalImportadas > 0 ? round(($guiasConciliadas / $totalImportadas) * 100, 1) : 0;

        return [
            Stat::make('Guias Pendentes', number_format($totalGuiasPendentes, 0, ',', '.'))
                ->description('Total de guias não lançadas na rastreabilidade')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('danger'),
                
            Stat::make('Beneficiários Afetados', number_format($totalBeneficiarios, 0, ',', '.'))
                ->description('Pacientes com guias pendentes')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Taxa de Conciliação', "{$taxaSucesso}%")
                ->description(number_format($guiasConciliadas, 0, ',', '.') . ' guias cruzadas com sucesso')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}