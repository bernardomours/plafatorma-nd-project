<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BirthdayGreetings extends TableWidget
{
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $unidadesPermitidas = ($user->unit_id == 1) ? [1] : [2, 3, 4];

        $query = Patient::query()
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->where('ativo', true);

        if (!$user->isAdmin() && !$user->isManager()) {
            
            if ($user->unit_id) {
                $query->whereIn('unit_id', $unidadesPermitidas);
            } else {
                $query->whereRaw('1 = 0'); 
            }
        }
        return $table
            ->query($query)
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
                    ->placeholder('Sem unidade'),
            ])
            ->emptyStateHeading('Ninguém soprando velinhas hoje')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated(false); 
    }
}