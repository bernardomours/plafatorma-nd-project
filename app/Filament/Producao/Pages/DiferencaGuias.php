<?php

namespace App\Filament\Producao\Pages;

use App\Models\UnpresentedGuide;
use App\Models\Appointment;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Models\Therapy;
use App\Models\Patient;
use App\Models\Professional;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;

class DiferencaGuias extends Page implements HasTable
{

    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $title = 'Diferença de Guias';
    protected static ?string $navigationLabel = 'Diferença de Guias';
    //protected static string|UnitEnum|null $navigationGroup = 'Frequência';
    protected string $view = 'filament.producao.pages.diferenca-guias';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar_nao_apresentadas')
                ->label('Importar Guias Não Apresentadas')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    Select::make('unidade_relatorio')
                        ->label('Unidade do Relatório')
                        ->options([
                            'Mossoró' => 'Mossoró (Limeira e Carvalho)',
                            'Natal' => 'Natal (Martins e Leal)',
                        ])
                        ->required(),
                        
                    FileUpload::make('arquivo_csv')
                        ->label('CSV de Guias Não Apresentadas')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['arquivo_csv']);
                    $unidadeSelecionada = str_replace([' (Limeira e Carvalho)', ' (Martins e Leal)'], '', $data['unidade_relatorio']); 
                    
                    $file = fopen($filePath, 'r');
                    fgetcsv($file, 0, ';');

                    $importados = 0;
                    $numeroLinha = 1;
                    $errosDetalhados = [];

                    $todosPacientes = Patient::with(['unit', 'agreement'])->get();

                    $pacientesFiltrados = $todosPacientes->filter(function($p) use ($unidadeSelecionada) {
                        $nomeConvenio = $p->agreement->name ?? ''; 
                        $nomeUnidade = $p->unit->city ?? $p->unit->name ?? ''; 
                        
                        $ehUnimed = str_contains(strtolower($nomeConvenio), 'unimed');
                        $ehUnidadeCorreta = strtolower($nomeUnidade) === strtolower($unidadeSelecionada);

                        return $ehUnimed && $ehUnidadeCorreta;
                    });

                    while (($row = fgetcsv($file, 0, ';')) !== false) {
                        $numeroLinha++;
                        $row = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $row);

                        if (!isset($row[2]) || trim($row[2]) === '') continue; 

                        $numeroGuia = trim($row[2]);
                        $executanteCsv = strtoupper(trim($row[5] ?? '')); 
                        
                        if ($unidadeSelecionada === 'Mossoró' && !str_contains($executanteCsv, 'LIMEIRA')) {
                            continue; 
                        }
                        if ($unidadeSelecionada === 'Natal' && !str_contains($executanteCsv, 'MARTINS')) {
                            continue; 
                        }

                        $motivosErroLinha = [];

                        $requestDate = null;
                        try {
                            if (!empty(trim($row[3]))) {
                                $requestDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($row[3]))->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            $motivosErroLinha[] = "Data de solicitação inválida";
                        }

                        $procedimentoBruto = strtoupper(trim($row[0] ?? ''));
                        $patientNameCsv = trim($row[1] ?? '');

                        $terapiaNome = 'INDEFINIDA';
                        if (str_contains($procedimentoBruto, 'ABA')) $terapiaNome = 'ABA';
                        elseif (str_contains($procedimentoBruto, 'DENVER')) $terapiaNome = 'DENVER';
                        elseif (str_contains($procedimentoBruto, 'FONO')) $terapiaNome = 'FONOAUDIOLOGIA';
                        elseif (str_contains($procedimentoBruto, 'PSICOMOTRICIDADE')) $terapiaNome = 'PSICOMOTRICIDADE';
                        elseif (str_contains($procedimentoBruto, 'TO -') || str_contains($procedimentoBruto, 'TERAPIA OCUPACIONAL')) $terapiaNome = 'TERAPIA OCUPACIONAL';
                        elseif (str_contains($procedimentoBruto, 'PSICO')) $terapiaNome = 'PSICOTERAPIA';
                        
                        $therapy = \App\Models\Therapy::firstOrCreate(['name' => $terapiaNome]);

                        // BUSCA INTELIGENTE DE PACIENTE
                        $patientSlugCsv = \Illuminate\Support\Str::slug($patientNameCsv);
                        $melhorPaciente = null; 
                        $maiorSimilaridadePaciente = 0;
                        
                        foreach ($pacientesFiltrados as $p) {
                            $dbSlug = \Illuminate\Support\Str::slug($p->name);
                            if (str_contains($dbSlug, $patientSlugCsv) || str_contains($patientSlugCsv, $dbSlug)) {
                                $melhorPaciente = $p; 
                                $maiorSimilaridadePaciente = 100; 
                                break;
                            }
                            $tamanho = min(strlen($dbSlug), strlen($patientSlugCsv));
                            if ($tamanho > 0) {
                                similar_text(substr($dbSlug, 0, $tamanho), substr($patientSlugCsv, 0, $tamanho), $porcentagem);
                                if ($porcentagem > $maiorSimilaridadePaciente) {
                                    $maiorSimilaridadePaciente = $porcentagem;
                                    $melhorPaciente = $p;
                                }
                            }
                        }
                        
                        $patient = ($melhorPaciente && $maiorSimilaridadePaciente >= 80) ? $melhorPaciente : null;

                        if (!$patient) {
                            $motivosErroLinha[] = "Paciente '{$patientNameCsv}' não encontrado (Guia salva sem vínculo)";
                        }

                        try {
                            UnpresentedGuide::updateOrCreate(
                                ['guide' => $numeroGuia],
                                [
                                    'procedure' => $procedimentoBruto,
                                    'patient_name' => $patientNameCsv,
                                    'professional_name' => null,
                                    'request_date' => $requestDate,
                                    'patient_id' => $patient->id ?? null,
                                    'professional_id' => null,
                                    'therapy_id' => $therapy->id,
                                ]
                            );

                            $importados++;
                        } catch (\Exception $e) {
                            $motivosErroLinha[] = "Erro ao salvar no banco de dados";
                        }

                        if (count($motivosErroLinha) > 0) {
                            $errosDetalhados[] = "<strong>Linha {$numeroLinha} (Guia {$numeroGuia}):</strong> " . implode(', ', $motivosErroLinha);
                        }
                    }

                    fclose($file);

                    if (count($errosDetalhados) > 0) {
                        $aviso = "Importamos <strong>{$importados}</strong> guias válidas para <strong>{$unidadeSelecionada}</strong>.<br><br><strong>Avisos/Erros encontrados:</strong><br>";
                        
                        $listaErros = array_slice($errosDetalhados, 0, 10);
                        foreach ($listaErros as $erro) {
                            $aviso .= "• {$erro}<br>";
                        }
                        
                        if (count($errosDetalhados) > 10) {
                            $aviso .= "<br><em>...e mais " . (count($errosDetalhados) - 10) . " avisos ocultados.</em>";
                        }
            
                        \Filament\Notifications\Notification::make()
                            ->title('Atenção ao importar')
                            ->body(str($aviso)->toHtmlString()) 
                            ->warning()
                            ->persistent() 
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Importação Realizada!')
                            ->body("Todas as {$importados} guias de {$unidadeSelecionada} foram importadas/atualizadas com sucesso, sem nenhum aviso.")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = UnpresentedGuide::query()
            ->whereNotIn('guide', function ($query) {
                $query->select('guide')
                      ->from('appointments')
                      ->whereNotNull('guide');
            });

        if (!$user->isAdmin()) { 
            $unidadesDoUsuario = $user->units->pluck('id')->toArray(); 
            
            $query->where(function ($q) use ($unidadesDoUsuario) {
                $q->whereHas('patient', function ($queryPaciente) use ($unidadesDoUsuario) {
                    $queryPaciente->whereIn('unit_id', $unidadesDoUsuario);
                })
                ->orWhereNull('patient_id'); 
            });
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('request_date')
                    ->label('Data Solicitação')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('guide')
                    ->label('Guia')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('patient_name')
                    ->label('Beneficiário')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('procedure')
                    ->label('Procedimento / Terapia')
                    ->searchable()
                    ->wrap(),

                // TextColumn::make('professional_name')
                //     ->label('Profissional Executante')
                //     ->searchable(),
            ])
            ->filters([
                SelectFilter::make('procedure')
                    ->label('Procedimento')
                    ->options(fn () => \App\Models\UnpresentedGuide::select('procedure')
                        ->whereNotNull('procedure')
                        ->distinct()
                        ->pluck('procedure', 'procedure')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('unit')
                    ->relationship('patient.unit', 'city') 
                    ->label('Prestador')
                    ->searchable()
                    ->preload(),

                Filter::make('guide_filter')
                    ->label('Guia')
                    ->form([
                        TextInput::make('guide_number')
                            ->label('Número da Guia')
                            ->placeholder('Ex: 18080557'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['guide_number'],
                            fn (Builder $query, $guide): Builder => $query->where('guide', 'like', "%{$guide}%"),
                        );
                    }),

                Filter::make('request_date')
                    ->columnSpan(2)
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('date_from')->label('Data Início'),
                            DatePicker::make('date_until')->label('Data Fim'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date),
                            );
                    })
            ], layout: FiltersLayout::AboveContent)
            ->recordUrl(null)
            ->recordAction(null)
            ->emptyStateHeading('Nenhuma diferença encontrada!')
            ->emptyStateDescription('Todas as guias não apresentadas constam na rastreabilidade.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Producao\Widgets\DiferencaGuiasStats::class,
            \App\Filament\Producao\Widgets\DiferencaGuiasProcedimentosChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}