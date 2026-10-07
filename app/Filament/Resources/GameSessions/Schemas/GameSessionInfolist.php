<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da partida')
                    ->schema([
                        TextEntry::make('user.name')->label('Participante'),
                        TextEntry::make('user.email')->label('E-mail'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (GameSessionStatus $state): string => match ($state) {
                                GameSessionStatus::Active => 'Ativa',
                                GameSessionStatus::Completed => 'Concluída',
                                GameSessionStatus::Abandoned => 'Abandonada',
                            }),
                        TextEntry::make('score')->label('Score')->numeric(),
                        TextEntry::make('found_words_count')
                            ->label('Palavras encontradas')
                            ->formatStateUsing(fn (int $state, GameSession $record): string => "{$state}/{$record->total_words}"),
                        TextEntry::make('duration_seconds')
                            ->label('Duração (segundos)')
                            ->placeholder('Em andamento'),
                        TextEntry::make('started_at')->label('Início')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('finished_at')->label('Término')->dateTime('d/m/Y H:i:s')->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
