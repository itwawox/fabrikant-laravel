<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsChanges;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use LogsChanges;

    /** @var list<string> служебные поля — в журнал не пишем */
    protected array $logIgnore = ['password', 'remember_token'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // Регистрации на сайте нет: пользователей заводит владелец, поэтому в админку пускаем всех заведённых.
    // Что кому видно внутри — роль (UserRole::areas)
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function canManage(string $area): bool
    {
        return in_array($area, $this->role->areas(), true);
    }
}
