<?php

namespace App\Filament\Producao\Resources\ProfessionalPaymentRules\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;


class ProfessionalPaymentRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes da Regra')
                    ->schema([
                        Select::make('professional_id')
                            ->label('Profissional')
                            ->relationship('professional', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('payment_type')
                            ->label('Tipo de Pagamento')
                            ->options([
                                'por_sessao' => 'Por Sessão (Padrão)',
                                'por_hora' => 'Por Hora (Ex: Humana/ABA)',
                                'por_dia' => 'Por Dia (Ex: Fono)',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('amount')
                            ->label('Valor (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                    ])->columns(3),

                Section::make('Filtros de Exceção')
                    ->description('Deixe em branco para aplicar a todos os convênios ou terapias deste profissional.')
                    ->schema([
                        Select::make('agreement_id')
                            ->label('Convênio Específico')
                            ->relationship('agreement', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('therapy_id')
                            ->label('Terapia Específica')
                            ->relationship('therapy', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
            ]);
    }
}
