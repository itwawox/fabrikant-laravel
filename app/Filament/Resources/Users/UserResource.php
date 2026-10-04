<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;
use UnitEnum;

class UserResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::USERS;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $modelLabel = 'пользователя';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Имя')->required()->maxLength(80),
            TextInput::make('email')->label('Почта для входа')->email()->required()->unique(ignoreRecord: true),
            Radio::make('role')
                ->label('Роль')
                ->options(UserRole::class)
                ->default(UserRole::Manager)
                ->required()
                // Последний владелец не может сам себя понизить — иначе управлять пользователями будет некому
                ->disabled(fn (?User $record): bool => $record !== null && self::isLastOwner($record)),
            TextInput::make('password')
                ->label('Пароль')
                ->password()
                ->revealable()
                ->minLength(10)
                ->required(fn (?User $record): bool => $record === null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (?User $record): string => $record
                    ? 'Пусто — пароль не меняется. Или отправьте ссылку для сброса кнопкой в списке.'
                    : 'Не меньше 10 знаков. Передайте его лично или отправьте ссылку для сброса пароля.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Имя')->weight('bold'),
                TextColumn::make('email')->label('Почта'),
                TextColumn::make('role')->label('Роль')->badge()->description(fn (User $record): string => $record->role->getDescription()),
            ])
            ->recordActions([
                Action::make('reset')
                    ->label('Ссылка для пароля')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => "На {$record->email} уйдёт письмо со ссылкой, по которой можно задать новый пароль.")
                    ->action(function (User $record) {
                        $status = Password::broker('users')->sendResetLink(['email' => $record->email]);
                        $status === Password::RESET_LINK_SENT
                            ? Notification::make()->success()->title('Письмо отправлено')->send()
                            : Notification::make()->danger()->title('Не получилось отправить: '.__($status))->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user()) || self::isLastOwner($record)),
            ]);
    }

    public static function isLastOwner(User $user): bool
    {
        return $user->role === UserRole::Owner && User::where('role', UserRole::Owner)->count() === 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
