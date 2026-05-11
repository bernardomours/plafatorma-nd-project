<?php

namespace App\Filament\Producao\Pages;

use App\Models\Professional;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\Unit;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;
use Filament\Schemas\Schema;
use Carbon\Carbon;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;

class FechamentoMensal extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Fechamento Mensal';
    protected static ?string $title = 'Fechamento de Produção';
    protected string $view = 'filament.producao.pages.fechamento-mensal';

    public ?array $data = [];
    protected array $cacheProducao = [];

    public function mount(): void
    {
        $this->form->fill([
            'mes' => date('m'),
            'ano' => date('Y'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtros de Apuração')
                    ->schema([
                        Select::make('mes')
                            ->label('Mês')
                            ->options([
                                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                                '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                                '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                                '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
                            ]),
                            
                        Select::make('ano')
                            ->label('Ano')
                            ->options(['2024' => '2024', '2025' => '2025', '2026' => '2026', '2027' => '2027']),

                        Select::make('professional_id')
                            ->label('Profissional')
                            ->options(Professional::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('therapy_id')
                            ->label('Terapia')
                            ->options(Therapy::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('unidades')
                            ->label('Unidade(s)')
                            ->options(Unit::pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload(),

                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function aplicarFiltros()
    {
        $this->cacheProducao = []; 
    }

    public function table(Table $table): Table
    {
        $mesFiltrado = $this->data['mes'] ?? date('m');
        $anoFiltrado = $this->data['ano'] ?? date('Y');
        $profId = $this->data['professional_id'] ?? null;
        $terapiaId = $this->data['therapy_id'] ?? null;
        $unidades = $this->data['unidades'] ?? [];

        $query = Professional::query()
            ->when($profId, fn($q) => $q->where('id', $profId))
            ->whereHas('appointments', function ($q) use ($mesFiltrado, $anoFiltrado, $terapiaId, $unidades) {
                $q->whereMonth('appointment_date', $mesFiltrado)
                  ->whereYear('appointment_date', $anoFiltrado);
                
                if ($terapiaId) {
                    $q->where('therapy_id', $terapiaId);
                }
                if (!empty($unidades)) {
                    $q->whereHas('patient', fn($p) => $p->whereIn('unit_id', $unidades));
                }
            });

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('Profissional')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_sessoes')
                    ->label('Sessões Feitas')
                    ->badge()
                    ->color('info')
                    ->state(fn (Professional $record) => $this->getResumoProducao($record)['sessoes'] . ' sessões'),

                TextColumn::make('valor_por_sessao')
                    ->label('Regra de Repasse')
                    ->badge()
                    ->color('gray')
                    ->state(fn (Professional $record) => $this->getResumoProducao($record)['valor_regra']),

                TextColumn::make('total_producao')
                    ->label('Valor a Receber (Bruto)')
                    ->money('BRL')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (Professional $record) => $this->getResumoProducao($record)['valor_total']),
            ])
            ->actions([
                Action::make('verExtrato')
                    ->label('Ver Extrato')
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->modalHeading(fn (Professional $record) => 'Extrato: ' . $record->name)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(function (Professional $record) use ($mesFiltrado, $anoFiltrado, $terapiaId, $unidades) {
                        
                        $detalhesQuery = Appointment::with(['patient', 'therapy'])
                            ->where('professional_id', $record->id)
                            ->whereMonth('appointment_date', $mesFiltrado)
                            ->whereYear('appointment_date', $anoFiltrado);

                        if ($terapiaId) {
                            $detalhesQuery->where('therapy_id', $terapiaId);
                        }
                        if (!empty($unidades)) {
                            $detalhesQuery->whereHas('patient', fn($q) => $q->whereIn('unit_id', $unidades));
                        }

                        $detalhes = $detalhesQuery->orderBy('appointment_date')->get();

                        return view('filament.producao.pages.extrato-detalhado', [
                            'profissional' => $record,
                            'atendimentos' => $detalhes,
                            'mes' => $mesFiltrado,
                            'ano' => $anoFiltrado
                        ]);
                    }),
            ]);
    }


    private function getResumoProducao(Professional $professional): array
    {
        if (isset($this->cacheProducao[$professional->id])) {
            return $this->cacheProducao[$professional->id];
        }

        $mes = $this->data['mes'] ?? date('m');
        $ano = $this->data['ano'] ?? date('Y');
        $terapiaId = $this->data['therapy_id'] ?? null;
        $unidades = $this->data['unidades'] ?? [];

        $query = Appointment::where('professional_id', $professional->id)
            ->whereMonth('appointment_date', $mes)
            ->whereYear('appointment_date', $ano)
            ->whereNotNull('check_in')
            ->whereNotNull('check_out');

        if ($terapiaId) $query->where('therapy_id', $terapiaId);
        if (!empty($unidades)) {
            $query->whereHas('patient', fn($q) => $q->whereIn('unit_id', $unidades));
        }

        $atendimentos = $query->get();
        $regras = $professional->paymentRules ?? collect();

        $valorTotal = 0;
        $totalSessoes = 0;
        $diasTrabalhadosFono = []; 
        $valoresAplicados = [];

        foreach ($atendimentos as $atendimento) {
            $paciente = clone $atendimento->patient; 
            $convenioId = $paciente ? $paciente->agreement_id : null;
            $tId = $atendimento->therapy_id;

            $regraAplicavel = $regras->where('agreement_id', $convenioId)->where('therapy_id', $tId)->first()
                           ?? $regras->whereNull('agreement_id')->where('therapy_id', $tId)->first()
                           ?? $regras->whereNull('agreement_id')->whereNull('therapy_id')->first();

            if ($regraAplicavel) {
                $valoresAplicados[(string)$regraAplicavel->amount] = true;
                
                $quantidade = $atendimento->session_number ?? 0;
                $totalSessoes += $quantidade;

                if ($regraAplicavel->payment_type === 'por_dia') {
                    $diaString = Carbon::parse($atendimento->appointment_date)->format('Y-m-d');
                    $diasTrabalhadosFono[$diaString] = $regraAplicavel->amount;
                } else {
                    $valorTotal += ($quantidade * $regraAplicavel->amount);
                }
            }
        }

        if (!empty($diasTrabalhadosFono)) {
            $valorTotal += array_sum($diasTrabalhadosFono);
        }

        $displayRegra = 'Sem Regra';
        if (count($valoresAplicados) > 1) {
            $displayRegra = 'Valores Variados';
        } elseif (count($valoresAplicados) === 1) {
            $displayRegra = 'R$ ' . number_format((float)array_key_first($valoresAplicados), 2, ',', '.');
        }

        $resultado = [
            'valor_total' => $valorTotal,
            'sessoes' => $totalSessoes,
            'valor_regra' => $displayRegra,
        ];

        $this->cacheProducao[$professional->id] = $resultado;
        return $resultado;
    }


    public function getTotaisGerais(): array
    {
        $mesFiltrado = $this->data['mes'] ?? date('m');
        $anoFiltrado = $this->data['ano'] ?? date('Y');
        $profId = $this->data['professional_id'] ?? null;
        $terapiaId = $this->data['therapy_id'] ?? null;
        $unidades = $this->data['unidades'] ?? [];

        $query = Professional::query()
            ->when($profId, fn($q) => $q->where('id', $profId))
            ->whereHas('appointments', function ($q) use ($mesFiltrado, $anoFiltrado, $terapiaId, $unidades) {
                $q->whereMonth('appointment_date', $mesFiltrado)
                  ->whereYear('appointment_date', $anoFiltrado);
                if ($terapiaId) $q->where('therapy_id', $terapiaId);
                if (!empty($unidades)) $q->whereHas('patient', fn($p) => $p->whereIn('unit_id', $unidades));
            });

        $profissionais = $query->get();
        
        $somaValores = 0;
        $somaSessoes = 0;
        
        foreach ($profissionais as $prof) {
            $resumo = $this->getResumoProducao($prof);
            $somaValores += $resumo['valor_total'];
            $somaSessoes += $resumo['sessoes'];
        }

        return [
            'valor' => $somaValores,
            'sessoes' => $somaSessoes
        ];
    }
}