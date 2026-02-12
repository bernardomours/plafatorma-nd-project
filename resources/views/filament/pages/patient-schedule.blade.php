<x-filament-panels::page>
    <div class="p-4 sm:p-6 lg:p-8 bg-white rounded-xl shadow-md">

        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $this->getPatient()->name }}</h1>
                <p class="text-lg text-gray-500">Acompanhamento de horários</p>
            </div>
            <div>
                {{ $this->addScheduleAction }}
            </div>
        </div>

        @php
            $daysOfWeek = [
                'segunda' => 'SEGUNDA',
                'terca'   => 'TERÇA',
                'quarta'  => 'QUARTA',
                'quinta'  => 'QUINTA',
                'sexta'   => 'SEXTA',
            ];
        @endphp

        <div class="border-t border-gray-200">
            <div class="grid grid-cols-5">
                @foreach ($daysOfWeek as $key => $dayName)
                    <div class="border-r border-gray-200 last:border-r-0">
                        <div class="py-3 px-4 bg-gray-50">
                            <h2 class="text-sm font-semibold text-center text-gray-600 tracking-wider">{{ $dayName }}</h2>
                        </div>
                                                <div class="p-4 space-y-3 min-h-[200px]">
                            @forelse ($this->getScheduleByDay($key) as $schedule)
                                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-3 relative group transition-all hover:shadow-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-blue-800 text-sm">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</p>
                                            <p class="font-bold text-gray-700 mt-1">{{ $schedule->therapy?->name ?? 'N/A' }}</p>
                                            <p class="text-sm text-gray-600">{{ ucfirst($schedule->type_therapy) }}</p>
                                            <p class="text-sm text-gray-500">Prof. {{ $schedule->professional?->name ?? 'N/A' }}</p>
                                        </div>

                                        <div class="flex flex-col items-center space-y-2 opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2">
                                            <button wire:click="mountAction('editSchedule', { record: '{{ $schedule->id }}' })" class="text-orange-500 hover:text-orange-700">
                                                <x-heroicon-o-pencil class="w-5 h-5"/>
                                            </button>
                                            <button wire:click="mountAction('deleteSchedule', { record: '{{ $schedule->id }}' })" class="text-red-500 hover:text-red-700">
                                                 <x-heroicon-o-x-mark class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex items-center justify-center h-full pt-10">
                                    <p class="text-sm text-gray-400">Sem agendamentos</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
