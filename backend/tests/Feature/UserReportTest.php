<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserReportTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminsGetAPaginatedReportWithoutSensitiveFields(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(14)->user()->create();

        $this->actingAs($admin)
            ->getJson('/api/reports/users')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonStructure([
                'data' => [['id', 'first_name', 'last_name', 'email', 'mobile_phone', 'role', 'verified', 'created_at']],
                'links',
                'meta',
            ])
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token');
    }

    public function testUsersWithoutThePermissionAreForbidden(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user)->getJson('/api/reports/users')->assertForbidden();
        $this->actingAs($user)->get('/api/reports/users/export')->assertForbidden();
    }

    public function testGuestsAreUnauthorized(): void
    {
        $this->getJson('/api/reports/users')->assertUnauthorized();
        $this->getJson('/api/reports/users/export')->assertUnauthorized();
    }

    public function testTheReportCanBeFilteredByDateRangeAndRole(): void
    {
        $admin = User::factory()->admin()->create(['created_at' => '2026-01-01 09:00:00']);
        User::factory()->user()->create(['email' => 'early@example.com', 'created_at' => '2026-02-01 00:00:00']);
        User::factory()->user()->create(['email' => 'late@example.com', 'created_at' => '2026-02-10 23:59:59']);
        User::factory()->user()->create(['email' => 'outside@example.com', 'created_at' => '2026-03-01 00:00:00']);

        $this->actingAs($admin)
            ->getJson('/api/reports/users?from=2026-02-01&to=2026-02-10')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($admin)
            ->getJson('/api/reports/users?role=admin')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'admin');
    }

    public function testInvalidFiltersAreRejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/reports/users?from=yesterday&role=root&per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from', 'role', 'per_page']);

        $this->actingAs($admin)
            ->getJson('/api/reports/users?from=2026-02-10&to=2026-02-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function testTheExportIsACsvOfTheFilteredRows(): void
    {
        $admin = User::factory()->admin()->create(['created_at' => '2026-01-01 09:00:00']);
        User::factory()->user()->create(['first_name' => '=SUM(A1)', 'email' => 'in@example.com', 'created_at' => '2026-02-05 12:00:00']);
        User::factory()->user()->create(['email' => 'out@example.com', 'created_at' => '2026-03-05 12:00:00']);

        $response = $this->actingAs($admin)
            ->get('/api/reports/users/export?from=2026-02-01&to=2026-02-28&role=user')
            ->assertOk()
            ->assertDownload('user-report-'.now()->format('Y-m-d').'.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('ID,"First name","Last name",Email', $csv);
        $this->assertStringContainsString('in@example.com', $csv);
        $this->assertStringNotContainsString('out@example.com', $csv);
        $this->assertStringContainsString("'=SUM(A1)", $csv);
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
        $this->actingAs($admin)->getJson('/api/reports/users')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->getJson('/api/reports/users')->assertOk();

        return count(DB::getQueryLog());
    }
}
