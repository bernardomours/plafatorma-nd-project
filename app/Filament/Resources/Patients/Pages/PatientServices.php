<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\RequestedServices\Schemas\RequestedServiceForm;
use App\Models\RequestedService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ToggleColumn;


class PatientServices extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = PatientResource::class;
    protected string $view = 'filament.resources.patients.pages.patient-services';
    protected static ?string $title = 'Controle de Carga Horária';

    public ?string $month_year = '';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function clearFilter(): void
    {
        $this->month_year = '';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = RequestedService::query()->where('patient_id', $this->record->id);

                if (!empty($this->month_year)) {
                    $date = \Carbon\Carbon::parse($this->month_year);
                    $query->whereYear('month_year', $date->year)
                          ->whereMonth('month_year', $date->month);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('therapy.name')
                    ->label('TERAPIA')
                    ->weight('bold'),
                TextColumn::make('service_type')
                    ->label('TIPO ATENDIMENTO'),
                TextColumn::make('month_year')
                    ->label('MÊS/ANO')
                    ->date('F \\d\\e Y'),
                TextColumn::make('requisition_number')
                    ->label('REQUISIÇÃO'),
                TextColumn::make('requested_hours')
                    ->label('CH SOLICITADA'),
                TextColumn::make('approved_hours')
                    ->label('CH LIBERADA')
                    ->badge()
                    ->color('success'),
                TextColumn::make('planned_hours')
                    ->label('CH PLANEJADA')
                    ->badge()
                    ->color('info'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nova Solicitação')
                    ->icon('heroicon-o-plus')
                    ->model(RequestedService::class)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['patient_id'] = $this->record->id;
                        return $data;
                    })
                    ->form(RequestedServiceForm::getFormSchema()),
            ])
            ->emptyStateHeading('Nenhuma solicitação de carga horária encontrada para este paciente.');
    }
}
