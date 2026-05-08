<x-filament-panels::page>
    
    {{-- 1. O formulário com os novos filtros --}}
    <div class="p-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <form wire:submit="aplicarFiltros">
            {{ $this->form }}
            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                    Pesquisar Produção
                </x-filament::button>
            </div>
        </form>
    </div>

    @php $totais = $this->getTotaisGerais(); @endphp

    {{-- 2. GRID COM OS DOIS CARDS (Sessões e Dinheiro) --}}
    <div class="mt-6 flex w-full" style="gap: 1.5rem;">
        
        {{-- CARD 1: TOTAL DE SESSÕES (com flex-1) --}}
        <div class="flex-1 p-6 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex items-center gap-4">
            <div class="p-3 bg-info-50 rounded-full dark:bg-info-900/20">
                <x-filament::icon
                    icon="heroicon-o-users"
                    class="w-8 h-8 text-info-600 dark:text-info-400"
                />
            </div>
            <div>
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Sessões Realizadas</h2>
                <p class="text-3xl font-bold text-info-600 dark:text-info-400">
                    {{ number_format($totais['sessoes'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- CARD 2: TOTAL GERAL FINANCEIRO (com flex-1) --}}
        <div class="flex-1 p-6 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex items-center gap-4">
            <div class="p-3 bg-success-50 rounded-full dark:bg-success-900/20">
                <x-filament::icon
                    icon="heroicon-o-currency-dollar"
                    class="w-8 h-8 text-success-600 dark:text-success-400"
                />
            </div>
            <div>
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Geral da Clínica (Bruto)</h2>
                <p class="text-3xl font-bold text-success-600 dark:text-success-400">
                    R$ {{ number_format($totais['valor'], 2, ',', '.') }}
                </p>
            </div>
        </div>
        
    </div>
    {{-- 3. A tabela detalhada e com as novas colunas --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>

</x-filament-panels::page>