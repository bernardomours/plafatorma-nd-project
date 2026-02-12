<?php

namespace App\Filament\Widgets;

use App\Models\Professional;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Collection;

class BirthdayGreetingsStaff extends TableWidget
{
    protected static ?string $pollingInterval = null;

    public function getTableRecords(): Collection
    {
        $professionals = Professional::with('unit')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->get();

        $users = User::with('unit')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->get();

        return $professionals->concat($users);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Professional::query()->whereRaw('1 = 0')) 
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
