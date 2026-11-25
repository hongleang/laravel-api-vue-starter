<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function testUsersCanSignInAndGetTheirProfileBack(): void
    {
        $user = User::factory()->admin()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles', ['admin'])
            ->assertJsonPath('data.permissions', ['reports.view', 'users.create', 'users.delete', 'users.update', 'users.view'])
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $this->assertAuthenticatedAs($user);
    }

    public function testAWrongPasswordReturnsAFieldError(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function testEmailAndPasswordAreRequired(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function testLoginIsRateLimitedAfterFiveFailures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function testTheCurrentUserEndpointNeedsAuthentication(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'You need to sign in to do that.');
    }

    public function testTheCurrentUserEndpointReturnsRolesAndPermissions(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.roles', ['user'])
            ->assertJsonPath('data.permissions', []);
    }

    public function testUsersCanSignOut(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertGuest('web');
    }

    public function testGuestsCannotSignOut(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function testTheSpaOriginGetsACsrfCookie(): void
    {
        $this->withHeader('Origin', 'http://localhost:8080')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }
}
