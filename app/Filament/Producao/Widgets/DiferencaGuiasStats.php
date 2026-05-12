<?php

namespace App\Filament\Producao\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\UnpresentedGuide;
use App\Models\Appointment;

class DiferencaGuiasStats extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = auth()->user();

        $query = UnpresentedGuide::query()
            ->whereNotIn('guide', function ($q) {
                $q->select('guide')
                  ->from('appointments')
                  ->whereNotNull('guide');
            });

        if (!$user->isAdmin()) {
            $unidadesDoUsuario = $user->units->pluck('id')->toArray();
            $query->where(function ($q) use ($unidadesDoUsuario) {
                $q->whereHas('patient', function ($queryPaciente) use ($unidadesDoUsuario) {
                    $queryPaciente->whereIn('unit_id', $unidadesDoUsuario);
                })->orWhereNull('patient_id');
            });
        }

        $totalGuias = $query->count();
        $totalBeneficiarios = (clone $query)->distinct('patient_name')->count('patient_name');

        return [
            Stat::make('Guias Pendentes', number_format($totalGuias, 0, ',', '.'))
                ->description('Total de guias não lançadas na rastreabilidade')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('danger'),
                
            Stat::make('Beneficiários Afetados', number_format($totalBeneficiarios, 0, ',', '.'))
                ->description('Pacientes com guias pendentes')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}