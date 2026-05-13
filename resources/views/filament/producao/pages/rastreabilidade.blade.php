<x-filament-panels::page>
    {{-- Esse x-init avisa o Card para atualizar SEMPRE que a tabela processar algo --}}
    <div x-data x-init="
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                Livewire.dispatch('atualizar-cards');
            });
        });
    ">
        {{ $this->table }}
    </div>
</x-filament-panels::page>