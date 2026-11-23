<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function testTheSeederCreatesTheDemoAccountsWithTheirRoles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->sole();
        $user = User::where('email', 'user@example.com')->sole();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->can('users.delete'));
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->can('users.view'));
        $this->assertSame(22, User::count());
    }
}
