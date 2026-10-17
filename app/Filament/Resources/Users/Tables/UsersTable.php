<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'Administrador',
                        UserRole::Participant => 'Participante',
                    })
                    ->sortable(),
                TextColumn::make('game_sessions_count')
                    ->label('Partidas')
                    ->counts('gameSessions')
                    ->sortable(),
                TextColumn::make('best_completed_score')
                    ->label('Melhor score concluído')
                    ->numeric()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        UserRole::Admin->value => 'Administrador',
                        UserRole::Participant->value => 'Participante',
                    ]),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }
}
