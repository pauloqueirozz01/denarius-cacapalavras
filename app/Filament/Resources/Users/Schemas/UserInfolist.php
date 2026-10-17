<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conta')
                    ->schema([
                        TextEntry::make('name')->label('Nome'),
                        TextEntry::make('email')->label('E-mail'),
                        TextEntry::make('role')
                            ->label('Perfil')
                            ->formatStateUsing(fn (UserRole $state): string => match ($state) {
                                UserRole::Admin => 'Administrador',
                                UserRole::Participant => 'Participante',
                            }),
                        TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
