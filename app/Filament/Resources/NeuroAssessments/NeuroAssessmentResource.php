<?php

namespace App\Filament\Resources\NeuroAssessments;

use App\Filament\Resources\NeuroAssessments\Pages\CreateNeuroAssessment;
use App\Filament\Resources\NeuroAssessments\Pages\EditNeuroAssessment;
use App\Filament\Resources\NeuroAssessments\Pages\ListNeuroAssessments;
use App\Filament\Resources\NeuroAssessments\Schemas\NeuroAssessmentForm;
use App\Filament\Resources\NeuroAssessments\Tables\NeuroAssessmentsTable;
use App\Filament\Resources\NeuroAssessments\RelationManagers\SessionsRelationManager;
use App\Models\NeuroAssessment;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NeuroAssessmentResource extends Resource
{
    protected static ?string $model = NeuroAssessment::class;

    protected static string|UnitEnum|null $navigationGroup = 'Frequência';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard';
    protected static ?string $modelLabel = 'Avaliação Neuro';
    protected static ?string $pluralModelLabel = 'Avaliações Neuro';
    protected static ?string $navigationLabel = 'Avaliações Neuro';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return NeuroAssessmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NeuroAssessmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeuroAssessments::route('/'),
            'create' => CreateNeuroAssessment::route('/create'),
            'edit' => EditNeuroAssessment::route('/{record}/edit'),
        ];
    }
}
