<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo Gerencial - Núcleo Desenvolve</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        header { border-bottom: 2px solid #48D1CC; padding-bottom: 15px; margin-bottom: 15px; }
        .logo-container { float: left; width: 30%; }
        .info-container { float: right; width: 70%; text-align: right; }
        .info-container h1 { margin: 0; font-size: 18px; color: #2C3E50; text-transform: uppercase; }
        .info-container p { margin: 4px 0 0 0; font-size: 11px; color: #7F8C8D; }
        .clear { clear: both; }
        
        /* WIDGETS DE KPI */
        .widgets-table { width: 100%; margin-bottom: 15px; border-spacing: 8px; border-collapse: separate; }
        .widget-card { background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px; vertical-align: top; width: 33%; }
        .widget-title { font-size: 9px; color: #6B7280; text-transform: uppercase; margin-bottom: 6px; display: block; }
        .widget-value { font-size: 18px; font-weight: bold; color: #111827; }
        .widget-list { margin: 4px 0 0 0; padding-left: 12px; font-size: 9px; color: #374151; list-style-type: none; }
        .widget-list li { margin-bottom: 2px; border-bottom: 1px solid #eee; padding-bottom: 2px; }

        /* CAIXA DO GRÁFICO */
        .chart-box { 
            margin-bottom: 15px; 
            padding: 12px; 
            border: 1px solid #E5E7EB; 
            border-radius: 8px; 
            background-color: #fff; 
            page-break-inside: avoid; 
        }
        .chart-title { font-size: 10px; font-weight: bold; color: #374151; margin-bottom: 10px; text-transform: uppercase; border-left: 4px solid #48D1CC; padding-left: 8px; }

        /* GRÁFICO DIÁRIO (COLUNAS VERTICAIS) */
        .daily-grid { width: 100%; border-spacing: 1px; table-layout: fixed; margin-top: 2px; }
        .daily-bar-container { vertical-align: bottom; height: 50px; text-align: center; } 
        .daily-bar { background-color: #48D1CC; width: 85%; border-radius: 1px 1px 0 0; margin: 0 auto; }
        .daily-day-label { font-size: 6px; color: #9CA3AF; padding-top: 2px; border-top: 1px solid #E5E7EB; line-height: 1; }
        .daily-value-label { font-size: 6px; font-weight: bold; color: #48D1CC; margin-bottom: 1px; }

        /* TABELA DE DADOS */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th { background-color: #48D1CC; color: white; padding: 6px; text-align: left; text-transform: uppercase; font-size: 9px; }
        .data-table td { border-bottom: 1px solid #E5E7EB; padding: 6px; font-size: 9px; }
        .data-table tr:nth-child(even) { background-color: #F9FAFB; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #E5E7EB !important; }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/nd-mossoro-icon.png'))) }}" style="max-height: 40px;" alt="Logo Núcleo Desenvolve">
        </div>
        <div class="info-container">
            <h1>Relatório de Atendimentos</h1>
            <p>Emitido em: {{ now()->timezone('America/Fortaleza')->format('d/m/Y H:i') }}</p>
        </div>
        <div class="clear"></div>
    </header>

    <main>
        <table class="widgets-table">
            <tr>
                <td class="widget-card">
                    <span class="widget-title">Total de Sessões (Mês)</span>
                    <span class="widget-value">{{ $totalSessoes }}</span>
                </td>
                <td class="widget-card">
                    <span class="widget-title">Média Diária</span>
                    <span class="widget-value">{{ $mediaDiaria }}</span>
                </td>
                <td class="widget-card">
                    <span class="widget-title">Sessões por Terapia</span>
                    <ul class="widget-list">
                        @foreach($sessoesPorTerapia as $terapia => $total)
                            <li><strong>{{ $terapia }}:</strong> {{ $total }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        </table>

        <div class="chart-box">
            <div class="chart-title">Sessões Diárias - {{ $mesSelecionado }}/{{ $anoSelecionado }}</div>
            <table class="daily-grid">
                <tr>
                    @php
                        $diasNoMes = cal_days_in_month(CAL_GREGORIAN, (int)$mesSelecionado, (int)$anoSelecionado);
                        $maxDiario = $evolucaoDiaria->max() ?: 1;
                        
                        // Definimos a altura máxima da barra em pixels (px) para o PDF não se perder
                        $alturaMaximaPx = 35; 
                    @endphp

                    @for ($i = 1; $i <= $diasNoMes; $i++)
                        @php 
                            $diaPad = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $valorDia = $evolucaoDiaria->get($diaPad, 0);
                            
                            // Agora a conta gera um valor em pixels, não mais em porcentagem
                            $alturaBarraPx = ($valorDia / $maxDiario) * $alturaMaximaPx;
                        @endphp
                        <td class="daily-bar-container">
                            @if($valorDia > 0)
                                <div class="daily-value-label">{{ $valorDia }}</div>
                                <div class="daily-bar" style="height: {{ $alturaBarraPx }}px;"></div>
                            @else
                                <div style="height: 1px; background: #F3F4F6; width: 70%; margin: 0 auto;"></div>
                            @endif
                            <div class="daily-day-label">{{ $diaPad }}</div>
                        </td>
                    @endfor
                </tr>
            </table>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="15%">Ref.</th>
                    <th>Paciente</th>
                    <th>Terapia</th>
                    <th class="text-center" width="15%">Sessões</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumo as $item)
                    <tr>
                        <td>{{ $item->reference_month }}</td>
                        <td>{{ $item->patient_name }}</td>
                        <td>{{ $item->therapy_name }}</td>
                        <td class="text-center">{{ $item->total_sessions }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right">SOMA TOTAL NO MÊS:</td>
                    <td class="text-center">{{ $totalSessoes }}</td>
                </tr>
            </tfoot>
        </table>
    </main>

</body>
</html>