<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The API path the SPA calls, rebuilt from the emailed SPA link.
     */
    private function apiPathFromEmail(User $user): string
    {
        $link = (new VerifyEmail)->toMail($user)->actionUrl;

        $this->assertStringStartsWith('http://localhost:8080/verify-email/', $link);

        return '/api/email/verify/'.substr($link, strlen('http://localhost:8080/verify-email/'));
    }

    public function testTheEmailedLinkVerifiesTheAddress(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson($this->apiPathFromEmail($user))
            ->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function testATamperedSignatureIsRejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson($this->apiPathFromEmail($user).'0')
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function testAnotherUsersLinkIsRejected(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $this->actingAs($other)
            ->postJson($this->apiPathFromEmail($owner))
            ->assertForbidden();

        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
    }

    public function testGuestsCannotVerify(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson($this->apiPathFromEmail($user))->assertUnauthorized();
    }

    public function testTheVerificationEmailCanBeResent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertAccepted();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testVerifiedUsersAreNotSentAnotherEmail(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/email/verification-notification')
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function testUnverifiedUsersCannotManageUsers(): void
    {
        $this->actingAs(User::factory()->unverified()->admin()->create())
            ->getJson('/api/users')
            ->assertForbidden();
    }
}
