<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Atendimentos</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        
        header { width: 100%; border-bottom: 2px solid #48D1CC; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-container { float: left; width: 30%; }
        .logo { max-height: 55px; }
        .info-container { float: right; width: 70%; text-align: right; }
        .info-container h1 { margin: 0; font-size: 20px; color: #2C3E50; text-transform: uppercase; }
        .info-container p { margin: 4px 0 0 0; font-size: 11px; color: #7F8C8D; }
        .clear { clear: both; } 

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #48D1CC; color: white; padding: 10px 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { border-bottom: 1px solid #E5E7EB; padding: 10px 8px; color: #4B5563; }
        tr:nth-child(even) { background-color: #F9FAFB; } /* Efeito zebrado nas linhas */
        
        footer { position: fixed; bottom: -10px; left: 0px; right: 0px; height: 20px; font-size: 9px; text-align: center; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/nd-mossoro-icon.png'))) }}" class="logo" alt="Logo Núcleo Desenvolve">
        </div>
        <div class="info-container">
            <h1>Relatório de Atendimentos</h1>
            <p>Gerado em: {{ now()->timezone('America/Fortaleza')->format('d/m/Y \à\s H:i') }}</p>
            <p>Total de Sessões: {{ $atendimentos->sum('session_number') }}</p>
        </div>
        <div class="clear"></div>
    </header>

    <footer>
        Documento gerado automaticamente pelo sistema interno da empresa.
    </footer>

    <main>
        <table>
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Data</th>
                    <th>Terapia</th>
                    <th>Atendimento</th>
                    <th>Sessões</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($atendimentos as $item)
                    <tr>
                        <td>{{ $item->patient->name ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->appointment_date)->format('d/m/Y') }}</td>
                        <td>{{ $item->therapy->name ?? 'N/A' }}</td>
                        <td>{{ $item->serviceType->name ?? 'N/A' }}</td>
                        <td>{{ $item->session_number }}</td>
                        <td>{{ $item->check_in ?? 'N/A' }}</td>
                        <td>{{ $item->check_out ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

</body>
</html>