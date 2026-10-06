<?php

namespace App\Filament\Resources\FinancialTerms\Schemas;

use App\Enums\FinancialTermDifficulty;
use App\Models\FinancialTerm;
use App\Rules\UniqueNormalizedFinancialTerm;
use App\Services\FinancialTermNormalizer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FinancialTermForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conteúdo educativo')
                    ->description('O termo será normalizado automaticamente para uso futuro no caça-palavras.')
                    ->schema([
                        TextInput::make('term')
                            ->label('Termo')
                            ->required()
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                'normalized_term',
                                FinancialTermNormalizer::normalize($state ?? ''),
                            ))
                            ->rules(fn (?FinancialTerm $record): array => [
                                new UniqueNormalizedFinancialTerm($record?->getKey()),
                            ]),
                        TextInput::make('normalized_term')
                            ->label('Forma usada no tabuleiro')
                            ->helperText('Somente letras de A a Z, com no máximo 24 caracteres.')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('description')
                            ->label('Descrição educativa')
                            ->required()
                            ->maxLength(500)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Classificação')
                    ->schema([
                        Select::make('difficulty')
                            ->label('Dificuldade')
                            ->options(FinancialTermDifficulty::class)
                            ->required()
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->helperText('Somente termos ativos poderão ser sorteados no jogo.')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
