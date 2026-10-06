<?php

namespace App\Filament\Resources\FinancialTerms\Tables;

use App\Enums\FinancialTermDifficulty;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FinancialTermsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('term')
                    ->label('Termo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('normalized_term')
                    ->label('Forma no tabuleiro')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(45)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('difficulty')
                    ->label('Dificuldade')
                    ->badge()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('difficulty')
                    ->label('Dificuldade')
                    ->options(FinancialTermDifficulty::class),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Somente ativos')
                    ->falseLabel('Somente inativos')
                    ->placeholder('Todos'),
            ])
            ->defaultSort('term')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
