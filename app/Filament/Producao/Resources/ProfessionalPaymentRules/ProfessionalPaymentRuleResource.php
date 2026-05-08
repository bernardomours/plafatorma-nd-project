<?php

namespace App\Filament\Producao\Resources\ProfessionalPaymentRules;

use App\Filament\Producao\Resources\ProfessionalPaymentRules\Pages\CreateProfessionalPaymentRule;
use App\Filament\Producao\Resources\ProfessionalPaymentRules\Pages\EditProfessionalPaymentRule;
use App\Filament\Producao\Resources\ProfessionalPaymentRules\Pages\ListProfessionalPaymentRules;
use App\Filament\Producao\Resources\ProfessionalPaymentRules\Schemas\ProfessionalPaymentRuleForm;
use App\Filament\Producao\Resources\ProfessionalPaymentRules\Tables\ProfessionalPaymentRulesTable;
use App\Models\ProfessionalPaymentRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Tables\Table;

class ProfessionalPaymentRuleResource extends Resource
{
    protected static ?string $model = ProfessionalPaymentRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Regras de Pagamento';
    protected static ?string $modelLabel = 'Regra de Pagamento';
    protected static ?string $pluralModelLabel = 'Regras de Pagamento';
    //protected static string|UnitEnum|null $navigationGroup = 'Produção';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProfessionalPaymentRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfessionalPaymentRulesTable::configure($table);
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
            'index' => ListProfessionalPaymentRules::route('/'),
            'create' => CreateProfessionalPaymentRule::route('/create'),
            'edit' => EditProfessionalPaymentRule::route('/{record}/edit'),
        ];
    }
}
