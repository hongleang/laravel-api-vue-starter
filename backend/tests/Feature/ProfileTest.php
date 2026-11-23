<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function testUsersCanUpdateTheirProfile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'first_name' => 'Grace',
                'last_name' => 'Hopper',
                'mobile_phone' => '0400 000 000',
                'email' => $user->email,
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Grace')
            ->assertJsonPath('message', 'Profile updated.');

        $this->assertSame('Hopper', $user->fresh()->last_name);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function testChangingTheEmailRequiresVerifyingItAgain(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'new@example.com',
        ])->assertOk();

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testProfileFieldsAreValidated(): void
    {
        $taken = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson('/api/profile', ['first_name' => '', 'email' => $taken->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }

    public function testUsersCanChangeTheirPassword(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function testTheCurrentPasswordMustBeCorrect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'wrong',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function testGuestsCannotUpdateAProfile(): void
    {
        $this->patchJson('/api/profile', [])->assertUnauthorized();
        $this->putJson('/api/profile/password', [])->assertUnauthorized();
    }
}
