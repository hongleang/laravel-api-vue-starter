<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function testAResetLinkIsEmailedAndPointsAtTheSpa(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'http://localhost:8080/reset-password/'.$notification->token)
                && str_contains($url, 'email=ada%40example.com');
        });
    }

    public function testTheEmailMustBeValidAndKnown(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'not-an-email'])
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertJsonValidationErrors('email');

        Notification::assertNothingSent();
    }

    public function testThePasswordCanBeResetWithAValidToken(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->postJson('/api/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertOk();

            $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

            return true;
        });
    }

    public function testAnInvalidTokenIsRejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'token' => 'invalid',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertJsonValidationErrors('email');

        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function testThePasswordMustBeConfirmed(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'token' => 'token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'different',
        ])->assertJsonValidationErrors('password');
    }
}
