<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use Filament\Actions;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\ServiceType;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Filament\Forms\Components\Select;

class ListAppointments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            // O SEU BOTÃO DE PDF ORIGINAL
            Actions\Action::make('export_pdf')
                ->label('Exportar para PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()->is_admin)
                ->action(function ($livewire) {
                    $atendimentos = $livewire->getFilteredTableQuery()->get();

                    $pdf = Pdf::loadView('pdf.appointments-table-pdf', [
                        'atendimentos' => $atendimentos
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()), 
                        'relatorio-atendimentos.pdf'
                    );
                }),
                
            // O NOVO BOTÃO DE EXCEL / CSV
            Actions\Action::make('export_csv')
                ->label('Exportar para Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success') // Verdinho padrão Excel
                ->visible(fn (): bool => auth()->user()->is_admin)
                ->action(function ($livewire) {
                    $atendimentos = $livewire->getFilteredTableQuery()->get();

                    $csvFileName = 'atendimentos-' . date('d-m-Y') . '.csv';

                    return response()->streamDownload(function () use ($atendimentos) {
                        $file = fopen('php://output', 'w');
                        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                        $separador = ';';
                        fputcsv($file, [
                            'Nome do Paciente', 
                            'Data', 
                            'Terapia', 
                            'Tipo de Atendimento', 
                            'Qtd de Sessoes', 
                            'Check-in', 
                            'Check-out'
                        ], $separador);

                        // Preenche as linhas com os dados
                        foreach ($atendimentos as $atendimento) {
                            fputcsv($file, [
                                $atendimento->patient->name ?? '-',
                                $atendimento->appointment_date ? \Carbon\Carbon::parse($atendimento->appointment_date)->format('d/m/Y') : '-',
                                $atendimento->therapy->name ?? '-',
                                $atendimento->serviceType->name ?? '-',
                                $atendimento->session_number ?? '0',
                                $atendimento->check_in ?? '-',
                                $atendimento->check_out ?? '-',
                            ], $separador);
                        }

                        fclose($file);
                    }, $csvFileName, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="'.$csvFileName.'"',
                    ]);
                }),

            Actions\Action::make('importar_unimed')
                    ->label('Importar CSV Unimed')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()->is_admin)
                    ->form([
                        Select::make('unidade_relatorio')
                            ->label('Unidade do Relatório')
                            ->options([
                                'Mossoró' => 'Mossoró',
                                'Natal' => 'Natal',
                                'João Câmara' => 'João Câmara',
                            ])
                            ->required(),
                            
                        FileUpload::make('arquivo_csv')
                            ->label('Arquivo CSV da Unimed')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('local')->path($data['arquivo_csv']);
                        $unidadeSelecionada = $data['unidade_relatorio']; 
                        
                        $file = fopen($filePath, 'r');
                        fgetcsv($file, 0, ';'); // Pula o cabeçalho

                        $importados = 0;
                        $numeroLinha = 1; 
                        $errosDetalhados = []; 

                        $todosPacientes = Patient::all();
                        $todosProfissionais = Professional::all();

                        while (($row = fgetcsv($file, 0, ';')) !== false) {
                            $numeroLinha++; 
                            
                            $row = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $row);

                            if (!isset($row[1]) || trim($row[1]) === '') continue; 

                            $motivosErroLinha = []; 

                            // Captura o número da Guia (Coluna C - Índice 2 do CSV), sem bloquear se for vazio
                            $numeroGuia = trim($row[2] ?? '');

                            // 1. Validação da Data
                            $appointmentDate = null;
                            try {
                                $appointmentDate = Carbon::createFromFormat('d/m/Y', trim($row[1]))->format('Y-m-d');
                            } catch (\Exception $e) {
                                $motivosErroLinha[] = "Data inválida ou em branco";
                            }

                            // 2. Validação de Horários 
                            $checkinBruto = trim($row[13] ?? '');
                            $checkoutBruto = trim($row[14] ?? '');
                            $checkIn = explode(' ', $checkinBruto)[1] ?? null;
                            $checkOut = explode(' ', $checkoutBruto)[1] ?? null;

                            if (!$checkIn) $motivosErroLinha[] = "Check-in ausente";
                            if (!$checkOut) $motivosErroLinha[] = "Check-out ausente";

                            // 3. Mapeamento de Terapias
                            $procedimentoBruto = strtoupper(trim($row[9]));
                            $terapiaNome = 'INDEFINIDA';
                            $tipoAtendimentoNome = 'Clínica'; 

                            if (str_contains($procedimentoBruto, 'ABA')) {
                                $terapiaNome = 'ABA';
                                if (str_contains($procedimentoBruto, 'DOMICILIAR')) {
                                    $tipoAtendimentoNome = 'Domiciliar';
                                } elseif (str_contains($procedimentoBruto, 'ESCOLAR')) {
                                    $tipoAtendimentoNome = 'Escolar';
                                }
                            } elseif (str_contains($procedimentoBruto, 'DENVER')) {
                                $terapiaNome = 'DENVER';
                            } elseif (str_contains($procedimentoBruto, 'PSICOPEDAGOGIA')) {
                                $terapiaNome = 'PSICOPEDAGOGIA';
                            } elseif (str_contains($procedimentoBruto, 'FONO')) { 
                                $terapiaNome = 'FONOAUDIOLOGIA';
                            } elseif (str_contains($procedimentoBruto, 'PSICOMOTRICIDADE')) {
                                $terapiaNome = 'PSICOMOTRICIDADE';
                            } elseif (str_contains($procedimentoBruto, 'TO -') || str_contains($procedimentoBruto, 'TERAPIA OCUPACIONAL')) {
                                $terapiaNome = 'TERAPIA OCUPACIONAL';
                            } elseif (str_contains($procedimentoBruto, 'TERAPIA ALIMENTAR')) {
                                $terapiaNome = 'TERAPIA ALIMENTAR';
                            } elseif (str_contains($procedimentoBruto, 'FISIO')) {
                                $terapiaNome = 'FISIOTERAPIA';
                            } elseif (str_contains($procedimentoBruto, 'ANAMNESE')) {
                                $terapiaNome = 'ANAMNESE';
                            } elseif (str_contains($procedimentoBruto, 'AVALIA')) {
                                $terapiaNome = 'AVALIAÇÃO';
                            } elseif (str_contains($procedimentoBruto, 'PSICO')) {
                                $terapiaNome = 'PSICOTERAPIA';
                            } else {
                                $terapiaNome = $procedimentoBruto;
                            }

                            $therapy = Therapy::firstOrCreate(['name' => $terapiaNome]);
                            $serviceType = ServiceType::firstOrCreate(['name' => $tipoAtendimentoNome]);

                            // 4. Validação de Nomes (Convênio + Unidade + Match Parcial)
                            $patientNameCsv = trim($row[6]);
                            $professionalNameCsv = trim($row[11]);
                            
                            $patientSlugCsv = \Illuminate\Support\Str::slug($patientNameCsv);
                            $professionalSlugCsv = \Illuminate\Support\Str::slug($professionalNameCsv);

                            $pacientesFiltrados = $todosPacientes->filter(function($p) use ($unidadeSelecionada) {
                                // ATENÇÃO: Ajuste as duas linhas abaixo para os nomes reais do seu banco
                                $nomeConvenio = $p->agreement->name ?? ''; 
                                $nomeUnidade = $p->unit->city ?? '';       

                                $ehUnimed = str_contains(strtolower($nomeConvenio), 'unimed');
                                $ehUnidadeCorreta = strtolower($nomeUnidade) === strtolower($unidadeSelecionada);

                                return $ehUnimed && $ehUnidadeCorreta;
                            });

                            $patient = $pacientesFiltrados->first(function($p) use ($patientSlugCsv) {
                                $dbSlug = \Illuminate\Support\Str::slug($p->name);
                                if (str_contains($dbSlug, $patientSlugCsv) || str_contains($patientSlugCsv, $dbSlug)) {
                                    return true;
                                }
                                similar_text($dbSlug, $patientSlugCsv, $porcentagem);
                                return $porcentagem >= 85;
                            });

                            $professional = $todosProfissionais->first(function($pro) use ($professionalSlugCsv) {
                                $dbSlug = \Illuminate\Support\Str::slug($pro->name);
                                if (str_contains($dbSlug, $professionalSlugCsv) || str_contains($professionalSlugCsv, $dbSlug)) {
                                    return true;
                                }
                                similar_text($dbSlug, $professionalSlugCsv, $porcentagem);
                                return $porcentagem >= 85;
                            });

                            if (!$patient) {
                                $motivosErroLinha[] = "Paciente '{$patientNameCsv}' (Unimed - {$unidadeSelecionada}) não encontrado";
                            }
                            if (!$professional) {
                                $motivosErroLinha[] = "Profissional '{$professionalNameCsv}' não encontrada(o)";
                            }

                            if (count($motivosErroLinha) > 0) {
                                $errosDetalhados[] = "<strong>Linha {$numeroLinha}:</strong> " . implode(', ', $motivosErroLinha);
                                continue; 
                            }

                            // 5. Salva no banco (Lógica de Guia Opcional)
                            $sessionNumber = isset($row[10]) ? (int) trim($row[10]) : 1; 

                            try {
                                if (!empty($numeroGuia)) {
                                    // Se tem guia, faz o updateOrCreate para evitar duplicidade
                                    Appointment::updateOrCreate(
                                        ['guide' => $numeroGuia], 
                                        [
                                            'appointment_date' => $appointmentDate,
                                            'check_in'         => $checkIn,
                                            'check_out'        => $checkOut,
                                            'session_number'   => $sessionNumber,
                                            'patient_id'       => $patient->id,
                                            'professional_id'  => $professional->id,
                                            'therapy_id'       => $therapy->id,
                                            'service_type_id'  => $serviceType->id,
                                        ]
                                    );
                                } else {
                                    // Se NÃO tem guia, apenas cria o registro e deixa a guia nula
                                    Appointment::create([
                                        'guide'             => null, 
                                        'appointment_date' => $appointmentDate,
                                        'check_in'         => $checkIn,
                                        'check_out'        => $checkOut,
                                        'session_number'   => $sessionNumber,
                                        'patient_id'       => $patient->id,
                                        'professional_id'  => $professional->id,
                                        'therapy_id'       => $therapy->id,
                                        'service_type_id'  => $serviceType->id,
                                    ]);
                                }
                                
                                $importados++; 
                            } catch (\Exception $e) {
                                $errosDetalhados[] = "<strong>Linha {$numeroLinha}:</strong> Erro ao salvar no banco.";
                            }
                        }

                        fclose($file);

                        // 6. Notificações
                        if (count($errosDetalhados) > 0) {
                            $aviso = "Importamos <strong>{$importados}</strong> atendimentos com sucesso.<br><br><strong>Erros encontrados:</strong><br>";
                            
                            $listaErros = array_slice($errosDetalhados, 0, 10);
                            foreach ($listaErros as $erro) {
                                $aviso .= "• {$erro}<br>";
                            }
                            
                            if (count($errosDetalhados) > 10) {
                                $aviso .= "<br><em>...e mais " . (count($errosDetalhados) - 10) . " erros ocultados. Corrija na planilha ou cadastre os nomes no sistema e tente novamente.</em>";
                            }

                            Notification::make()
                                ->title('Atenção ao importar')
                                ->body(str($aviso)->toHtmlString()) 
                                ->warning()
                                ->persistent() 
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Importação Perfeita!')
                                ->body("Todos os {$importados} atendimentos foram importados/atualizados com sucesso, sem nenhum erro.")
                                ->success()
                                ->send();
                        }
                    }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}