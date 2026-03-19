<x-filament-panels::page>
    
    {{ $this->form }}

    @if($professional_id)
        @php
            $total = \App\Models\PatientService::where(function ($query) use ($professional_id) {
                            $query->where('coordinator_id', $professional_id)
                                  ->orWhere('supervisor_id', $professional_id);
                        })
                        ->whereHas('patient')
                        ->distinct('patient_id')
                        ->count('patient_id');
        @endphp
        
        <div class="fi-ta-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-white/10">
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                Total de Pacientes Vinculados: 
            </h2>
            <div class="text-3xl font-black text-primary-600 dark:text-primary-400">
                {{ $total }}
            </div>
        </div>
    @endif

    {{ $this->table }}

</x-filament-panels::page>