<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'peternak',
            'remember_token' => Str::random(10),
        ];
    }

    /** Akun ABK milik peternak tertentu (atau peternak baru bila tidak diberikan). */
    public function abk(?\App\Models\User $peternak = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\User::ROLE_ABK,
            'peternak_id' => $peternak?->id ?? \App\Models\User::factory(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
