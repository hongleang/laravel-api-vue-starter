<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'mobile_phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
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

    /**
     * Give the user the admin role, with every permission.
     */
    public function admin(): static
    {
        return $this->withRole(Role::Admin);
    }

    /**
     * Give the user the basic user role.
     */
    public function user(): static
    {
        return $this->withRole(Role::User);
    }

    private function withRole(Role $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $model = RoleModel::findOrCreate($role->value, 'web');

            foreach ($role->permissions() as $permission) {
                $model->givePermissionTo(PermissionModel::findOrCreate($permission->value, 'web'));
            }

            $user->assignRole($model);
        });
    }
}
