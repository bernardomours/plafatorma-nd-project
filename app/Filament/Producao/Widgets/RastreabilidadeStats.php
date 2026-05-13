<?php

namespace App\Filament\Producao\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Producao\Pages\Rastreabilidade;
use Livewire\Attributes\On; // <-- 1. IMPORTAÇÃO DO OUVINTE

class RastreabilidadeStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;
    protected static bool $isDiscovered = false;

    protected function getTablePage(): string
    {
        return Rastreabilidade::class;
    }

    // 2. A MÁGICA: Escuta o evento da página e atualiza na mesma hora!
    #[On('atualizar-cards')]
    public function atualizarEstatisticas(): void
    {
        // O simples fato de receber o evento faz o Livewire re-renderizar este Card
    }

    protected function getStats(): array
    {
        $totalSessoes = $this->getPageTableQuery()->sum('session_number');

        return [
            Stat::make('Quantidade de Sessões', number_format($totalSessoes, 0, ',', '.'))
                ->description('Total conforme os filtros aplicados abaixo')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }
}