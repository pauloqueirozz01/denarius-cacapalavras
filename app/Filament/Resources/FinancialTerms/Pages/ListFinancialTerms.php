<?php

namespace App\Filament\Resources\FinancialTerms\Pages;

use App\Filament\Resources\FinancialTerms\FinancialTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialTerms extends ListRecords
{
    protected static string $resource = FinancialTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
