<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminsGetAPaginatedListWithoutSensitiveFields(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(14)->user()->create();

        $this->actingAs($admin)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonStructure([
                'data' => [['id', 'first_name', 'last_name', 'email', 'mobile_phone', 'email_verified_at', 'created_at', 'roles', 'can' => ['update', 'delete']]],
                'links',
                'meta',
            ])
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token');
    }

    public function testUsersWithoutThePermissionAreForbidden(): void
    {
        $this->actingAs(User::factory()->user()->create())
            ->getJson('/api/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'You do not have permission to do that.');
    }

    public function testGuestsAreUnauthorized(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function testTheListCanBeSearchedFilteredAndSorted(): void
    {
        $admin = User::factory()->admin()->create(['first_name' => 'Mia']);
        User::factory()->user()->create(['first_name' => 'Zebulon', 'email' => 'zeb@example.com']);
        User::factory()->user()->create(['first_name' => 'Aaron']);

        $this->actingAs($admin)
            ->getJson('/api/users?filter[search]=zebulon')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'zeb@example.com');

        $this->actingAs($admin)
            ->getJson('/api/users?filter[role]=admin')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Mia');

        $this->actingAs($admin)
            ->getJson('/api/users?sort=-first_name')
            ->assertJsonPath('data.0.first_name', 'Zebulon')
            ->assertJsonPath('data.2.first_name', 'Aaron');
    }

    public function testInvalidFiltersAreRejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/users?sort=password&per_page=1000&filter[role]=root')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'per_page', 'filter.role']);
    }

    public function testSearchTreatsWildcardsAsLiteralCharacters(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/users?filter[search]=%25')
            ->assertJsonCount(0, 'data');
    }

    public function testQueryCountDoesNotGrowWithRows(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->count(2)->user()->create();
        $small = $this->countQueries($admin);

        User::factory()->count(8)->user()->create();
        $large = $this->countQueries($admin);

        $this->assertSame($small, $large);
    }

    /**
     * Warm the permission cache first so only the queries that depend on the rows are counted.
     */
    private function countQueries(User $admin): int
    {
        $this->actingAs($admin)->getJson('/api/users')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->getJson('/api/users')->assertOk();

        return count(DB::getQueryLog());
    }
}
