<?php

namespace App\Filament\Widgets;

use App\Models\Professional;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BirthdayGreetingsProfessionals extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
        ->query(
            Professional::query()
                ->whereMonth('birth_date', now()->month)
                ->whereDay('birth_date', now()->day)
        )
        ->heading('Profissionais')
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
