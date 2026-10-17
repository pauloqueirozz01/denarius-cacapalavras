<?php

namespace App\Filament\Resources\FinancialTerms;

use App\Filament\Resources\FinancialTerms\Pages\CreateFinancialTerm;
use App\Filament\Resources\FinancialTerms\Pages\EditFinancialTerm;
use App\Filament\Resources\FinancialTerms\Pages\ListFinancialTerms;
use App\Filament\Resources\FinancialTerms\Pages\ViewFinancialTerm;
use App\Filament\Resources\FinancialTerms\Schemas\FinancialTermForm;
use App\Filament\Resources\FinancialTerms\Schemas\FinancialTermInfolist;
use App\Filament\Resources\FinancialTerms\Tables\FinancialTermsTable;
use App\Models\FinancialTerm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinancialTermResource extends Resource
{
    protected static ?string $model = FinancialTerm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'term';

    protected static ?string $modelLabel = 'termo financeiro';

    protected static ?string $pluralModelLabel = 'termos financeiros';

    protected static ?string $navigationLabel = 'Termos financeiros';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return FinancialTermForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinancialTermInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialTermsTable::configure($table);
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
            'index' => ListFinancialTerms::route('/'),
            'create' => CreateFinancialTerm::route('/create'),
            'view' => ViewFinancialTerm::route('/{record}'),
            'edit' => EditFinancialTerm::route('/{record}/edit'),
        ];
    }
}
