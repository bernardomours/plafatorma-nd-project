<x-filament-panels::page>
    
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

    {{ $this->table }}

</x-filament-panels::page>