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
        // 1. Identifica o usuário logado e define a região dele
        $user = auth()->user();
        $unidadesPermitidas = ($user->unit_id == 1) ? [1] : [2, 3, 4];

        // 2. Prepara a busca ignorando o bloqueio padrão para aplicarmos a nossa regra regional
        $query = Patient::withoutGlobalScopes()
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day);

        // 3. Se NÃO for Admin, aplica o filtro da região dele
        if (!$user->is_admin) {
            $query->whereIn('unit_id', $unidadesPermitidas);
        }

        return $table
            ->query($query) // Injeta a nossa busca inteligente aqui!
            ->heading('Pacientes')
            ->description('🎂 Aniversariantes do Dia')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->icon('heroicon-m-cake')
                    ->iconColor('primary'),

                // Aqui continua igual, pois paciente só tem 1 unidade
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