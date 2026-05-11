<x-filament-panels::page>
    <div class="p-4 bg-yellow-50 text-yellow-800 rounded-md text-xs dark:bg-yellow-900/30 dark:text-yellow-400">
        Quantidade geral de guias presentes no relatório de guias não apresentadas e ausente no relatório de rastreabilidade cirúrgica.
    </div>
    {{-- Se quisermos colocar os gráficos de Ranking depois, eles vão entrar aqui em cima! --}}

    {{-- Renderiza a tabela mágica que você construiu no PHP --}}
    {{ $this->table }}
</x-filament-panels::page>