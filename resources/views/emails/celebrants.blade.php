<h2>🎉 Aniversariantes do dia! - Núcleo Desenvolve</h2>
<p>Hoje estamos celebrando mais um ano para os seguintes aniversariantes:</p>

<ul>
    @foreach($celebrants as $pessoa)
        <li style="margin-bottom: 10px;">
            <span style="background-color: #eee; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                {{ $pessoa->tipo_pessoa }}
            </span>
            <br>
            <strong>{{ $pessoa->name }}</strong> 
            <br>
            <small>Unidade: 
                @if($pessoa->tipo_pessoa === 'Profissional(is)')
                    {{ $pessoa->units->pluck('city')->join(', ') ?: 'N/A' }}
                @else
                    {{ $pessoa->unit->city ?? 'N/A' }}
                @endif
            </small>
        </li>
    @endforeach
</ul>

<p>Núcleo Desenvolve deseja um feliz aniversário!</p>