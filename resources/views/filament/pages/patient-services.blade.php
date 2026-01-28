<x-filament-panels::page>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start mb-4 border-b pb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Controle de Carga Horária</h1>
                <p class="text-sm text-gray-500 mt-1">Solicitações de carga horária do paciente {{ $this->getPatient()->name }}.</p>
            </div>
            <div class="flex items-center">
                @foreach ($this->getCachedHeaderActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </div>

        <div class="mb-4">
            <form wire:submit.prevent="filter" class="flex items-end space-x-3">
                <div class="flex-grow">
                    <label for="month_year" class="block text-sm font-medium text-gray-700">Filtrar por Mês/Ano</label>
                    <input type="month" id="month_year" wire:model.defer="month_year"
                           class="fi-input block w-full border-gray-300 rounded-lg shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 mt-1"
                           placeholder="Selecione o mês e ano">
                </div>
                <div>
                    <button type="submit"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-500 focus:ring-primary-500/50">
                        Filtrar
                    </button>
                </div>
                <div>
                    <button type="button" wire:click="clearFilter"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-gray-200 text-gray-950 hover:bg-gray-300 focus:ring-gray-400/50">
                        Limpar
                    </button>
                </div>
            </form>
        </div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
