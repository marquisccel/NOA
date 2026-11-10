<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PETERNAK = 'peternak';

    public const ROLE_ABK = 'abk';

    public const ROLE_PENGAWAS = 'pengawas';

    public const ROLES = [self::ROLE_PETERNAK, self::ROLE_ABK, self::ROLE_PENGAWAS];

    public function kandang(): HasMany
    {
        return $this->hasMany(Kandang::class);
    }

    public function isPeternak(): bool
    {
        return $this->role === self::ROLE_PETERNAK;
    }

    public function isAbk(): bool
    {
        return $this->role === self::ROLE_ABK;
    }

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
        ];
    }
}
