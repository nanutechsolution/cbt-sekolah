<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\AkunService;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Saya')
                    ->description('Hanya nama dan password yang dapat diubah di sini. Username dan peran akun tidak dapat diubah sendiri.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->trim()
                            ->autofocus(),
                        TextInput::make('username')
                            ->label('Username')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->minLength(10)
                            ->maxLength(255)
                            ->rule(fn (Get $get): array => app(AkunService::class)->passwordRules(
                                wajib: false,
                                username: $this->getUser()->username,
                                name: (string) $get('name'),
                            ))
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->helperText('Kosongkan jika tidak ingin mengubah password. Minimal 10 karakter dan tidak boleh sama dengan username atau nama.'),
                        TextInput::make('passwordConfirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required()
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                        TextInput::make('currentPassword')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->autocomplete('current-password')
                            ->currentPassword(guard: 'web')
                            ->required()
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(fn (Get $get): bool => filled($get('password')))
                            ->helperText('Wajib diisi jika mengganti password.'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return app(AkunService::class)->perbaruiProfil($record, $data);
    }

    protected function afterSave(): void
    {
        $this->data['currentPassword'] = null;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil berhasil diperbarui.';
    }
}
