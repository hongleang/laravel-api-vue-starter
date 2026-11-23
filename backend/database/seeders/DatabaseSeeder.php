<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
        ])->assignRole(Role::Admin->value);

        User::factory()->create([
            'first_name' => 'Uma',
            'last_name' => 'User',
            'email' => 'user@example.com',
        ])->assignRole(Role::User->value);

        User::factory()->count(20)->create()->each(
            fn (User $user) => $user->assignRole(Role::User->value)
        );
    }
}
