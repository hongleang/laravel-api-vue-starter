<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'mobile_phone' => '+44 20 7946 0000',
            'email' => 'ada@example.com',
            'role' => 'user',
            ...$overrides,
        ];
    }

    public function testAdminsCanCreateAUserWhoGetsAResetLink(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/users', $this->payload(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonPath('data.roles', ['admin']);

        $user = User::where('email', 'ada@example.com')->sole();

        $this->assertTrue($user->hasRole('admin'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testCreatingAUserIsValidated(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/users', ['email' => 'taken@example.com', 'role' => 'root'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'role']);
    }

    public function testUsersWithoutThePermissionCannotCreateUsers(): void
    {
        $this->actingAs(User::factory()->user()->create())
            ->postJson('/api/users', $this->payload())
            ->assertForbidden();

        $this->assertNull(User::where('email', 'ada@example.com')->first());
    }

    public function testAdminsCanViewAUser(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles', ['user']);
    }

    public function testUsersCannotViewOtherUsers(): void
    {
        $other = User::factory()->create();

        $this->actingAs(User::factory()->user()->create())
            ->getJson("/api/users/{$other->id}")
            ->assertForbidden();
    }

    public function testMissingUsersReturnAJsonNotFound(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/users/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource could not be found.');
    }

    public function testAdminsCanUpdateAUserAndChangeTheirRole(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/users/{$user->id}", $this->payload(['email' => $user->email, 'role' => 'admin']))
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ada')
            ->assertJsonPath('data.roles', ['admin']);

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertFalse($user->fresh()->hasRole('user'));
    }

    public function testUpdatingKeepsTheRoleWhenNoneIsSent(): void
    {
        $user = User::factory()->user()->create();
        $payload = $this->payload(['email' => $user->email]);
        unset($payload['role']);

        $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/users/{$user->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.roles', ['user']);
    }

    public function testAdminsCannotChangeTheirOwnRole(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson("/api/users/{$admin->id}", $this->payload(['email' => $admin->email, 'role' => 'user']))
            ->assertJsonValidationErrors('role');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function testUsersCannotUpdateOtherUsers(): void
    {
        $other = User::factory()->create(['first_name' => 'Original']);

        $this->actingAs(User::factory()->user()->create())
            ->putJson("/api/users/{$other->id}", $this->payload(['email' => $other->email]))
            ->assertForbidden();

        $this->assertSame('Original', $other->fresh()->first_name);
    }

    public function testUpdatingIsValidated(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/users/{$user->id}", ['email' => $taken->email])
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }

    public function testAdminsCanDeleteOtherUsers(): void
    {
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted.');

        $this->assertModelMissing($user);
    }

    public function testAdminsCannotDeleteThemselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/users/{$admin->id}")
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function testUsersWithoutThePermissionCannotDelete(): void
    {
        $other = User::factory()->create();

        $this->actingAs(User::factory()->user()->create())
            ->deleteJson("/api/users/{$other->id}")
            ->assertForbidden();

        $this->assertModelExists($other);
    }

    public function testGuestsCannotManageUsers(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/users', $this->payload())->assertUnauthorized();
        $this->putJson("/api/users/{$user->id}", $this->payload())->assertUnauthorized();
        $this->deleteJson("/api/users/{$user->id}")->assertUnauthorized();
    }

    public function testTheResourceSaysWhatTheViewerMayDo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson("/api/users/{$admin->id}")
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', false);
    }
}
