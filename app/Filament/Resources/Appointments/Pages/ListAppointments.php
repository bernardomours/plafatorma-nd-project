<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    // Pega exatamente os dados que estão aparecendo na tabela (com os filtros da tela aplicados!)
                    $atendimentos = $livewire->getFilteredTableQuery()->get();

                    $csvFileName = 'atendimentos-' . date('d-m-Y') . '.csv';

                    return response()->streamDownload(function () use ($atendimentos) {
                        $file = fopen('php://output', 'w');

                        // MÁGICA 1: Adiciona o BOM UTF-8 para o Excel ler acentos (ç, ã, á) perfeitamente
                        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                        // MÁGICA 2: Separador com ';' (Padrão do Excel no Brasil)
                        $separador = ';';

                        // Cabeçalhos das Colunas no Excel (Seguindo a ordem da sua tabela na tela)
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
                                $atendimento->attendance_type ?? '-',
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
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}