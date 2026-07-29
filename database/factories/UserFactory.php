<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'nom_complet' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mot_de_passe' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_APPRENANT,
        ];
    }

    public function formateur(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_FORMATEUR]);
    }

    public function superviseur(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_SUPERVISEUR]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }
}
