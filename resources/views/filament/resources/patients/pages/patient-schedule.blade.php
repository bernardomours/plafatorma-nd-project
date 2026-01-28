<x-filament-panels::page>
    {{-- Seção superior: Grade de Horários Fixos --}}
    <x-filament::section>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->record->nome }}
                </h1>
                <p class="text-sm text-gray-500">Grade de Horários Fixos</p>
            </div>
            <x-filament::button wire:click="mountAction('createSchedule')">
                Adicionar Horário
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-5 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
            @php
                $dias = [
                    'segunda' => 'SEGUNDA',
                    'terca' => 'TERÇA',
                    'quarta' => 'QUARTA',
                    'quinta' => 'QUINTA',
                    'sexta' => 'SEXTA'
                ];
                $schedules = $this->getSchedules();
            @endphp

            @foreach($dias as $key => $dia)
                <div class="flex flex-col border-b sm:border-b-0 sm:border-r border-gray-200 dark:border-gray-700 last:border-0">
                    {{-- Cabeçalho do Dia --}}
                    <div class="bg-gray-50 dark:bg-white/5 p-2 text-center border-b border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] font-bold tracking-widest text-gray-400">{{ $dia }}</span>
                    </div>

                    {{-- Conteúdo do Dia --}}
                    <div class="p-3 min-h-[160px] space-y-3">
                        @php
                            $schedulesDoDia = $schedules->where('dia_semana', $key);
                        @endphp

                        @if($schedulesDoDia->isNotEmpty())
                            @foreach($schedulesDoDia as $schedule)
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-2 relative group shadow-sm">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-blue-700 dark:text-blue-400 font-bold text-[10px] italic">
                                            {{ \Carbon\Carbon::parse($schedule->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->hora_fim)->format('H:i') }}
                                        </span>
                                        <div class="flex items-center space-x-1">
                                            <button wire:click="mountAction('editSchedule', { schedule: {{ $schedule->id }} })" class="text-gray-400 hover:text-gray-600 text-xs">✏️</button>
                                            <button wire:click="mountAction('deleteSchedule', { schedule: {{ $schedule->id }} })" class="text-red-400 hover:text-red-600 text-xs">❌</button>
                                        </div>
                                    </div>
                                    <div class="font-bold text-[9px] uppercase leading-tight text-gray-800 dark:text-gray-200">{{ $schedule->therapy->name ?? 'N/A' }}</div>
                                    <div class="text-[9px] text-gray-500 uppercase italic">{{ $schedule->tipo_atendimento }}</div>
                                    <div class="text-[9px] text-gray-400 mt-1">{{ $schedule->professional->name ?? 'N/A' }}</div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-[10px] text-gray-400 text-center mt-8 italic px-2">Sem agendamentos</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Seção inferior: Controle de Carga Horária (mantido como está) --}}
    <x-filament::section class="mt-8">
        {{-- ... (código existente) ... --}}
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
