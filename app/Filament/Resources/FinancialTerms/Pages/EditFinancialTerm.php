<?php

namespace App\Filament\Resources\FinancialTerms\Pages;

use App\Filament\Resources\FinancialTerms\FinancialTermResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialTerm extends EditRecord
{
    protected static string $resource = FinancialTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
