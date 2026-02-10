<h2>🎉 Aniversariantes do dia! - Núcleo Desenvolve</h2>
<p>Não se esqueça de dar os parabéns para:</p>

<ul>
    @foreach($celebrants as $pessoa)
        <li style="margin-bottom: 10px;">
            <span style="background-color: #eee; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                {{ $pessoa->tipo_pessoa }}
            </span>
            <br>
            <strong>{{ $pessoa->name }}</strong> 
            <br>
            <small>Unidade: {{ $pessoa->unit->city ?? 'N/A' }}</small>
        </li>
    @endforeach
</ul>

<p>Núcleo Desenvolve deseja um feliz aniversário!</p>
