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
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStats::class,
        ];
    }
}
