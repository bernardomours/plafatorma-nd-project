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
                    FileUpload::make('arquivo_csv')
                        ->label('CSV de Guias Não Apresentadas')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['arquivo_csv']);
                    $file = fopen($filePath, 'r');
                    fgetcsv($file, 0, ';'); // Pula cabeçalho

                    $importados = 0;
                    $numeroLinha = 1;
                    $errosDetalhados = [];

                    // Carrega todo mundo para a nossa busca inteligente
                    $todosPacientes = Patient::all();
                    $todosProfissionais = Professional::all();

                    while (($row = fgetcsv($file, 0, ';')) !== false) {
                        $numeroLinha++;
                        $row = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $row);

                        $numeroGuia = trim($row[2] ?? '');
                        if (empty($numeroGuia)) continue;

                        try {
                            $requestDate = !empty(trim($row[3])) ? Carbon::createFromFormat('d/m/Y', trim($row[3]))->format('Y-m-d') : null;
                            
                            $procedimentoBruto = strtoupper(trim($row[0] ?? ''));
                            $patientNameCsv = trim($row[1] ?? '');
                            $professionalNameCsv = trim($row[5] ?? '');

                            $terapiaNome = 'INDEFINIDA';
                            if (str_contains($procedimentoBruto, 'ABA')) $terapiaNome = 'ABA';
                            elseif (str_contains($procedimentoBruto, 'DENVER')) $terapiaNome = 'DENVER';
                            elseif (str_contains($procedimentoBruto, 'FONO')) $terapiaNome = 'FONOAUDIOLOGIA';
                            elseif (str_contains($procedimentoBruto, 'PSICOMOTRICIDADE')) $terapiaNome = 'PSICOMOTRICIDADE';
                            elseif (str_contains($procedimentoBruto, 'TO -') || str_contains($procedimentoBruto, 'TERAPIA OCUPACIONAL')) $terapiaNome = 'TERAPIA OCUPACIONAL';
                            elseif (str_contains($procedimentoBruto, 'PSICO')) $terapiaNome = 'PSICOTERAPIA';
                            
                            $therapy = Therapy::firstOrCreate(['name' => $terapiaNome]);

                            $patientSlugCsv = \Illuminate\Support\Str::slug($patientNameCsv);
                            $professionalSlugCsv = \Illuminate\Support\Str::slug($professionalNameCsv);

                            $melhorPaciente = null; $maiorSimPaciente = 0;
                            foreach ($todosPacientes as $p) {
                                $dbSlug = \Illuminate\Support\Str::slug($p->name);
                                if (str_contains($dbSlug, $patientSlugCsv) || str_contains($patientSlugCsv, $dbSlug)) {
                                    $melhorPaciente = $p; $maiorSimPaciente = 100; break;
                                }
                                $tamanho = min(strlen($dbSlug), strlen($patientSlugCsv));
                                if ($tamanho > 0) {
                                    similar_text(substr($dbSlug, 0, $tamanho), substr($patientSlugCsv, 0, $tamanho), $porc);
                                    if ($porc > $maiorSimPaciente) { $maiorSimPaciente = $porc; $melhorPaciente = $p; }
                                }
                            }
                            $patientId = ($melhorPaciente && $maiorSimPaciente >= 80) ? $melhorPaciente->id : null;

                            $melhorProf = null; $maiorSimProf = 0;
                            foreach ($todosProfissionais as $pro) {
                                $dbSlug = \Illuminate\Support\Str::slug($pro->name);
                                if (str_contains($dbSlug, $professionalSlugCsv) || str_contains($professionalSlugCsv, $dbSlug)) {
                                    $melhorProf = $pro; $maiorSimProf = 100; break;
                                }
                                $tamanho = min(strlen($dbSlug), strlen($professionalSlugCsv));
                                if ($tamanho > 0) {
                                    similar_text(substr($dbSlug, 0, $tamanho), substr($professionalSlugCsv, 0, $tamanho), $porc);
                                    if ($porc > $maiorSimProf) { $maiorSimProf = $porc; $melhorProf = $pro; }
                                }
                            }
                            $professionalId = ($melhorProf && $maiorSimProf >= 80) ? $melhorProf->id : null;

                            UnpresentedGuide::updateOrCreate(
                                ['guide' => $numeroGuia],
                                [
                                    'procedure' => $procedimentoBruto,
                                    'patient_name' => $patientNameCsv,
                                    'professional_name' => $professionalNameCsv,
                                    'request_date' => $requestDate,
                                    'patient_id' => $patientId,
                                    'professional_id' => $professionalId,
                                    'therapy_id' => $therapy->id,
                                ]
                            );

                            $importados++;
                        } catch (\Exception $e) {
                            $errosDetalhados[] = "Linha {$numeroLinha}: Erro ao processar a guia {$numeroGuia}.";
                        }
                    }

                    fclose($file);

                    Notification::make()
                        ->title('Importação Concluída!')
                        ->body("Foram processadas {$importados} guias no sistema.")
                        ->success()
                        ->send();
                })
        ];
    }

    public function table(Table $table): Table
    {
        $query = UnpresentedGuide::query()
            ->whereNotIn('guide', function ($query) {
                $query->select('guide')
                      ->from('appointments')
                      ->whereNotNull('guide');
            });

        return $table
            ->query($query)
            ->columns([
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

                TextColumn::make('professional_name')
                    ->label('Profissional Executante')
                    ->searchable(),

                TextColumn::make('request_date')
                    ->label('Data Solicitação')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->emptyStateHeading('Nenhuma diferença encontrada!')
            ->emptyStateDescription('Todas as guias não apresentadas constam na rastreabilidade.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}