<?php

namespace App\Filament\Resources\Therapies;

use App\Filament\Resources\Therapies\Pages\CreateTherapy;
use App\Filament\Resources\Therapies\Pages\EditTherapy;
use App\Filament\Resources\Therapies\Pages\ListTherapies;
use App\Filament\Resources\Therapies\Schemas\TherapyForm;
use App\Filament\Resources\Therapies\Tables\TherapiesTable;
use App\Models\Therapy;
use App\Models\Unit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;

class TherapyResource extends Resource
{
    protected static ?string $model = Therapy::class;
    protected static ?string $modelLabel = 'Terapia';
    protected static ?string $pluralModelLabel = 'Terapias';
    protected static ?string $navigationLabel = 'Terapias';
    protected static string|UnitEnum|null $navigationGroup = 'Frequência';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TherapyForm::configure($schema);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns(array_merge(
            [
                TextColumn::make('name')
                    ->label('Terapia')
                    ->searchable(),
            ],
            Unit::all()->map(function (Unit $unit) {
                return ToggleColumn::make('unit_' . $unit->id)
                    ->label($unit->city)
                    ->disabled(fn () => ! auth()->user()->is_admin) #isso serve para caso o user nao seja admin, ele nao mexa
                    ->getStateUsing(fn ($record) => $record->units()->where('unit_id', $unit->id)->exists())
                    ->updateStateUsing(function ($record, $state) use ($unit) {
                        if ($state) {
                            $record->units()->syncWithoutDetaching([$unit->id]);
                        } else {
                            $record->units()->detach($unit->id);
                        }
                    });
            })->toArray()
        ));
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTherapies::route('/'),
            'create' => CreateTherapy::route('/create'),
            'edit' => EditTherapy::route('/{record}/edit'),
        ];
    }
}
