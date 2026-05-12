<?php

namespace App\Filament\Producao\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\UnpresentedGuide;
use Illuminate\Support\Facades\DB;

class DiferencaGuiasProcedimentosChart extends ChartWidget
{
    protected ?string $heading = 'Quantidade de Guias Pendentes por Procedimento';
    
    protected ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $user = auth()->user();

        $query = UnpresentedGuide::query()
            ->whereNotIn('guide', function ($q) {
                $q->select('guide')->from('appointments')->whereNotNull('guide');
            });

        if (!$user->isAdmin()) {
            $unidadesDoUsuario = $user->units->pluck('id')->toArray();
            $query->where(function ($q) use ($unidadesDoUsuario) {
                $q->whereHas('patient', function ($queryPaciente) use ($unidadesDoUsuario) {
                    $queryPaciente->whereIn('unit_id', $unidadesDoUsuario);
                })->orWhereNull('patient_id');
            });
        }

        $dados = $query->select('procedure', DB::raw('count(*) as total'))
            ->groupBy('procedure')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Guias Faltantes',
                    'data' => $dados->pluck('total')->toArray(),
                    'backgroundColor' => '#40E0D0',
                ],
            ],
            'labels' => $dados->pluck('procedure')->map(fn($p) => \Illuminate\Support\Str::limit($p, 30))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getColumns(): int
    {
        return 1;
    }
    
    public static function canView(): bool
    {
        return UnpresentedGuide::whereNotIn('guide', function ($q) {
            $q->select('guide')->from('appointments')->whereNotNull('guide');
        })->exists();
    }
}