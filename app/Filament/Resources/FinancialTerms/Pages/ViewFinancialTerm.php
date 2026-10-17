<?php

namespace App\Filament\Resources\FinancialTerms\Pages;

use App\Filament\Resources\FinancialTerms\FinancialTermResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialTerm extends ViewRecord
{
    protected static string $resource = FinancialTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
