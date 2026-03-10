<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Enums\ProfessionalRole;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Visit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('professional.name')
                    ->label('Profissional')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('happened_at')
                    ->label('Realizada em')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                BadgeColumn::make('type')
                    ->label('Tipo'),
                BadgeColumn::make('status')
                    ->label('Status'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(VisitType::class),
                SelectFilter::make('status')
                    ->label('Status')
                    ->default('pending')
                    ->options(VisitStatus::class),
                SelectFilter::make('professional_id')
                    ->label('Profissional')
                    ->relationship(
                        'professional',
                        'name',
                        fn (Builder $query) => $query->whereIn('role', [
                            ProfessionalRole::Coordinator,
                            ProfessionalRole::Supervisor,
                        ])
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('unidade')
                    ->label('Unidade')
                    ->options(\App\Models\Unit::pluck('city', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
            
                        // Vai até o paciente daquela coordenação e filtra pela unidade escolhida
                        return $query->whereHas('patient', function (Builder $q) use ($data) {
                            $q->where('unit_id', $data['value']);
                        });
                    }),
            ],layout: FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordClasses([
                'bg-blue-50' => fn (Visit $record): bool => $record->type === VisitType::Coordination,
            ]);
    }
}
