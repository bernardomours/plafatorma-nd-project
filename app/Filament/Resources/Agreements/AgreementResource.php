<?php

namespace App\Filament\Resources\Agreements;

use App\Filament\Resources\Agreements\Pages\CreateAgreement;
use App\Filament\Resources\Agreements\Pages\EditAgreement;
use App\Filament\Resources\Agreements\Pages\ListAgreements;
use App\Filament\Resources\Agreements\Schemas\AgreementForm;
use App\Models\Agreement;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Schemas\Schema;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;

class AgreementResource extends Resource
{
    protected static ?string $model = Agreement::class;

    protected static ?string $modelLabel = 'Convênio';
    protected static ?string $pluralModelLabel = 'Convênios';
    protected static ?string $navigationLabel = 'Convênios';
    protected static string|UnitEnum|null $navigationGroup = 'Serviços';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document';

    public static function form(Schema $schema): Schema
    {
        return AgreementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $units = Unit::all();

        $unitColumns = $units->map(function (Unit $unit) {
            return ToggleColumn::make('unit_' . $unit->id)
                ->label($unit->city)
                ->disabled(fn () => !auth()->user()->is_admin)
                ->getStateUsing(fn (Agreement $record) => $record->units->contains($unit->id))
                ->updateStateUsing(function (Agreement $record, $state) use ($unit) {
                    if ($state) {
                        $record->units()->syncWithoutDetaching([$unit->id]);
                    } else {
                        $record->units()->detach($unit->id);
                    }
                });
        })->toArray();

        return $table
            ->columns(array_merge([
                TextColumn::make('name')
                    ->label('Convênio')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Ativo'),
            ], $unitColumns));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('units');
        $user = auth()->user();

        if (! $user->is_admin) {
            return $query->whereHas('units', function ($q) use ($user) {
                $q->where('units.id', $user->unit_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgreements::route('/'),
            'create' => CreateAgreement::route('/create'),
            'edit' => EditAgreement::route('/{record}/edit'),
        ];
    }
}
