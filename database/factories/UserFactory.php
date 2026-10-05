<?php

namespace Database\Factories;

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Mot de passe par defaut.
     *
     * Il respecte volontairement la politique de l'application (12 caracteres,
     * majuscule, chiffre, symbole) : un mot de passe de test non conforme ferait
     * echouer les tests qui passent par les formulaires, et surtout un test
     * « echec de connexion » reussirait a tort parce qu'il aurait teste un autre
     * mot de passe que le sien.
     */
    public const PASSWORD = 'NereMining!2026';

    public function definition(): array
    {
        return [
            'matricule' => (string) $this->faker->unique()->numberBetween(1000, 999999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make(self::PASSWORD),
            'is_super_admin' => false,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['is_super_admin' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Compte bloque par saturation des tentatives, verrouillage encore actif. */
    public function lockedOut(): static
    {
        return $this->state(fn () => [
            'failed_attempts' => 0,
            'locked_until' => now()->addMinutes(15),
        ]);
    }

    /** Verrouillage echu : le compte doit pouvoir se reconnecter. */
    public function lockoutExpired(): static
    {
        return $this->state(fn () => [
            'failed_attempts' => 0,
            'locked_until' => now()->subMinute(),
        ]);
    }

    public function withFailedAttempts(int $attempts): static
    {
        return $this->state(fn () => ['failed_attempts' => $attempts]);
    }

    /**
     * Affecte un role dans un departement (cree la ligne de pivot).
     *
     * `refreshAuthorisations` est indispensable : sans lui, le modele garderait en
     * cache un etat anterieur de ses droits et les verifications de role de la
     * meme requete testeraient une donnee perimee.
     */
    public function withRole(Department $department, DepartmentRole $role, ?int $techLevel = null): static
    {
        return $this->afterCreating(function (User $user) use ($department, $role, $techLevel) {
            $user->departments()->attach($department, [
                'role' => $role->value,
                'tech_level' => $techLevel,
            ]);

            $user->unsetRelation('departments')->refreshAuthorisations();
        });
    }
}
