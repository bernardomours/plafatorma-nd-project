<div class="space-y-4">
    <p class="text-sm text-gray-500">
        Período Apurado: <strong>{{ $mes }}/{{ $ano }}</strong>
    </p>

    <div class="border border-gray-200 rounded-lg overflow-hidden dark:border-gray-700">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-700 font-medium dark:bg-gray-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Data do Atendimento</th>
                    <th class="px-4 py-3">Paciente</th>
                    <th class="px-4 py-3">Terapia</th>
                    <th class="px-4 py-3 text-center">Sessões (Qtd)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($atendimentos as $atendimento)
                    @php
                        $isGlosada = empty($atendimento->check_in) || empty($atendimento->check_out);
                    @endphp
                    
                    {{-- A linha fica vermelhinha com CSS inline para não depender do Tailwind --}}
                    <tr class="transition {{ $isGlosada ? '' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}"
                        @if($isGlosada) style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;" @endif>
                        
                        <td class="px-4 py-3">
                            {{ \Carbon\Carbon::parse($atendimento->appointment_date)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $atendimento->patient->name ?? 'Paciente não encontrado' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $atendimento->therapy->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">
                            @if($isGlosada)
                                {{-- Etiqueta Glosada forçada com CSS também --}}
                                <span style="background-color: #ef4444; color: white; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Glosada
                                </span>
                            @else
                                {{ $atendimento->session_number ?? 1 }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            Nenhum atendimento encontrado neste período.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 bg-yellow-50 text-yellow-800 rounded-md text-xs dark:bg-yellow-900/30 dark:text-yellow-400">
        <strong>Aviso Importante:</strong> Atendimentos marcados em vermelho como <strong class="text-red-600 dark:text-red-400">Glosada</strong> estão pendentes de Check-out no sistema e <strong>não</strong> foram contabilizados no valor a receber deste mês.
    </div>
</div>