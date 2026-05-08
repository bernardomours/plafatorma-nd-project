<?php

namespace App\Filament\Producao\Resources\ProfessionalPaymentRules\Pages;

use App\Filament\Producao\Resources\ProfessionalPaymentRules\ProfessionalPaymentRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfessionalPaymentRules extends ListRecords
{
    protected static string $resource = ProfessionalPaymentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
