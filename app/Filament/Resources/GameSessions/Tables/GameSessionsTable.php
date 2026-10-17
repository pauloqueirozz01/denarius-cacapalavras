<?php

namespace App\Filament\Resources\GameSessions\Tables;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Participante')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (GameSessionStatus $state): string => match ($state) {
                        GameSessionStatus::Active => 'Ativa',
                        GameSessionStatus::Completed => 'Concluída',
                        GameSessionStatus::Abandoned => 'Abandonada',
                    })
                    ->sortable(),
                TextColumn::make('score')->label('Score')->numeric()->sortable(),
                TextColumn::make('found_words_count')
                    ->label('Palavras')
                    ->formatStateUsing(fn (int $state, GameSession $record): string => "{$state}/{$record->total_words}"),
                TextColumn::make('duration_seconds')
                    ->label('Duração')
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? 'Em andamento'
                        : sprintf('%02d:%02d', intdiv($state, 60), $state % 60)),
                TextColumn::make('started_at')->label('Início')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('finished_at')->label('Término')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        GameSessionStatus::Active->value => 'Ativa',
                        GameSessionStatus::Completed->value => 'Concluída',
                        GameSessionStatus::Abandoned->value => 'Abandonada',
                    ]),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }
}
