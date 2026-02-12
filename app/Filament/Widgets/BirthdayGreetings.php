<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BirthdayGreetings extends TableWidget
{
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
        ->query(
            Patient::query()
                ->whereMonth('birth_date', now()->month)
                ->whereDay('birth_date', now()->day)
        )
        ->heading('Pacientes')
        ->description('🎂 Aniversariantes do Dia')
        ->columns([
            TextColumn::make('name')
                ->label('Nome')
                ->weight('bold')
                ->icon('heroicon-m-cake')
                ->iconColor('primary'),

            TextColumn::make('unit.city')
                ->label('Unidade')
                ->badge()
                ->color('info')
                ->separator(',')
                ->placeholder('Sem unidade'),

        ])
        ->emptyStateHeading('Ninguém soprando velinhas hoje')
        ->emptyStateIcon('heroicon-o-calendar-days')
        ->paginated(false); 
    }
}
