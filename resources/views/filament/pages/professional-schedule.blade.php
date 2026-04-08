<x-filament-panels::page>
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 p-6">
        {{ $this->form }}
    </div>

    @php
        $prof_id = $this->data['professional_id'] ?? null;
    @endphp

    @if($prof_id)
        @php
            $diasDaSemana = [1 => 'SEGUNDA', 2 => 'TERÇA', 3 => 'QUARTA', 4 => 'QUINTA', 5 => 'SEXTA'];
            $agenda = $this->getAgendaData();
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 overflow-hidden">
            
            @foreach(['Manhã', 'Tarde'] as $turno)
                <div class="bg-gray-100 dark:bg-white/5 px-4 py-3 border-b border-gray-200 dark:border-white/10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                        {{ $turno }}
                    </h3>
                </div>

                <div class="divide-x divide-gray-200 dark:divide-white/10 border-b border-gray-200 dark:border-white/10" style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));">
                    
                    @foreach($diasDaSemana as $numeroDia => $nomeDia)
                        <div class="flex flex-col">
                            <div class="text-center py-2 border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                                <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $nomeDia }}</span>
                            </div>

                            <div class="p-2 flex-1 min-h-[120px] bg-white dark:bg-gray-900">
                                @forelse($agenda[$turno][$numeroDia] as $horario)
                                    <div class="mb-2 p-2 rounded-md border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 flex flex-col shadow-sm transition hover:shadow-md">
                                        
                                        <div class="flex justify-between items-start mb-1.5">
                                            <div class="text-[12px] font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($horario->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->end_time)->format('H:i') }}
                                            </div>
                                            <div class="text-[11px] text-right leading-none ml-1">
                                                <div class="font-black text-blue-600 dark:text-blue-400 mb-1.5">{{ $horario->therapy->name ?? '' }}</div>
                                                <div class="text-gray-500 dark:text-gray-400">{{ $horario->serviceType->name ?? '' }}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">
                                            👤 {{ $horario->patient->name ?? 'Sem nome' }}
                                        </div>
                                        
                                    </div>
                                @empty
                                    <div class="h-full flex items-center justify-center p-2 text-center">
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Sem Agentamentos</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                    
                </div>
            @endforeach

        </div>
    @else
        <div class="flex flex-col items-center justify-center py-16 text-gray-500 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
            <x-heroicon-o-calendar-days class="text-gray-300 dark:text-gray-600 mb-4" style="width: 4rem; height: 4rem;" />
            <p class="text-lg font-medium text-gray-600 dark:text-gray-400">Selecione um profissional acima para visualizar a agenda.</p>
        </div>
    @endif
</x-filament-panels::page>