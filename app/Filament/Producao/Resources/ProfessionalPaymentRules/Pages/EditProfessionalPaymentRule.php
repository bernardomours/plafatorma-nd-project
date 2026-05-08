<?php

namespace App\Filament\Producao\Resources\ProfessionalPaymentRules\Pages;

use App\Filament\Producao\Resources\ProfessionalPaymentRules\ProfessionalPaymentRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProfessionalPaymentRule extends EditRecord
{
    protected static string $resource = ProfessionalPaymentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
