<x-filament-panels::page>
    
    {{-- O Formulário de Filtros (Mantido exatamente com o seu estilo) --}}
    <div class="p-4 mb-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <form wire:submit="aplicarFiltros">
            
            {{ $this->form }}

            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-m-funnel">
                    Aplicar Filtros
                </x-filament::button>
            </div>
            
        </form>
    </div>

    {{-- O Sistema de Abas (Tabs) --}}
    <x-filament::tabs label="Relatórios">
        <x-filament::tabs.item 
            :active="$activeTab === 'geral'"
            wire:click="$set('activeTab', 'geral')"
            icon="heroicon-m-chart-bar"
        >
            Relatório Geral
        </x-filament::tabs.item>

        <x-filament::tabs.item 
            :active="$activeTab === 'comparativo'"
            wire:click="$set('activeTab', 'comparativo')"
            icon="heroicon-m-arrow-trending-up"
        >
            Comparativo Dia x Dia
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- O Conteúdo de cada Aba --}}
    <div class="mt-6">
        @if($activeTab === 'geral')
            
            {{-- ABA 1: Mostra os seus widgets antigos e a Tabela --}}
            
            {{-- O widget de Stats grande em cima --}}
            <div class="mb-8">
                @livewire(\App\Filament\Resources\Appointments\Widgets\AppointmentStats::class, $this->getHeaderWidgetsData())
            </div>
            
            {{-- Gráficos forçados em caixas separadas com margem inferior (mb-8) --}}
            <div class="mb-8">
                @livewire(\App\Filament\Resources\Appointments\Widgets\AppointmentsPerDayChart::class, $this->getHeaderWidgetsData())
            </div>
            
            <div class="mb-8">
                @livewire(\App\Filament\Resources\Appointments\Widgets\AppointmentsByTypeChart::class, $this->getHeaderWidgetsData())
            </div>
            
            <div class="mb-8">
                @livewire(\App\Filament\Resources\Appointments\Widgets\AppointmentsByAgreementChart::class, $this->getHeaderWidgetsData())
            </div>
            
            <div class="mb-8">
                @livewire(\App\Filament\Resources\Appointments\Widgets\AppointmentsByUnitChart::class, $this->getHeaderWidgetsData())
            </div>

            {{-- A sua tabela padrão continua aqui! --}}
            {{ $this->table }}

        @elseif($activeTab === 'comparativo')
            
            {{-- ABA 2: Comparativo Mês x Mês --}}
            <div>
                <div class="mb-8">
                {{-- 1. Os Novos Cards de Estatísticas no Topo --}}
                @livewire(\App\Filament\Resources\Appointments\Widgets\ComparativoStatsWidget::class, $this->getHeaderWidgetsData())
                </div>

                {{-- 3. O Gráfico de Acumulado (Linhas) que já tínhamos criado --}}
                <div class="mb-8">
                    @livewire(\App\Filament\Resources\Appointments\Widgets\BusinessDaysChart::class, $this->getHeaderWidgetsData())
                </div>

                {{-- 2. O Novo Gráfico de Barras de Terapias por Dia da Semana --}}
                <div class="mb-8">
                    @livewire(\App\Filament\Resources\Appointments\Widgets\TherapiesByWeekdayChart::class, $this->getHeaderWidgetsData())
                </div>
                
                <x-filament::section>
                    <x-slot name="heading">Entenda o Comparativo de Dias Úteis</x-slot>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        O gráfico de linhas exibe o <strong>Crescimento Acumulado</strong> baseando-se nos dias úteis. 
                        A linha azul mostra o volume de atendimentos somados dia após dia do mês filtrado, e a linha tracejada mostra o mesmo período do mês anterior.
                    </p>
                </x-filament::section>
            </div>

        @endif
    </div>

</x-filament-panels::page>