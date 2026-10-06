<?php

namespace App\Filament\Resources\FinancialTerms\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialTermInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Termo financeiro')
                    ->schema([
                        TextEntry::make('term')->label('Termo'),
                        TextEntry::make('normalized_term')->label('Forma no tabuleiro'),
                        TextEntry::make('description')
                            ->label('Descrição educativa')
                            ->columnSpanFull(),
                        TextEntry::make('difficulty')
                            ->label('Dificuldade')
                            ->badge(),
                        IconEntry::make('is_active')
                            ->label('Ativo')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
