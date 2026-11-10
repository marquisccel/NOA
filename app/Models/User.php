<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// role dan peternak_id sengaja tidak mass-assignable: hanya diisi kode server (pendaftaran, pengelolaan ABK).
#[Fillable(['name', 'email', 'password'])]
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

    /** Peternak pemilik akun ABK ini. */
    public function peternak(): BelongsTo
    {
        return $this->belongsTo(self::class, 'peternak_id');
    }

    /** Akun ABK milik peternak ini (F8.2). */
    public function abk(): HasMany
    {
        return $this->hasMany(self::class, 'peternak_id');
    }

    /** Id peternak pemilik data yang boleh dicatat akun ini: dirinya sendiri, atau peternak majikan ABK. */
    public function idPemilikData(): ?int
    {
        return $this->isAbk() ? $this->peternak_id : $this->id;
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
