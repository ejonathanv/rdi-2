<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_and_delete_push_subscription(): void
    {
        $user = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-'.uniqid();

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlD4lkL_HqpHvb3_iQ7X2F3v2s3v2s3v2s3v2s3v2s',
                    'auth' => 'tBHItJI17pajftbm5UOwNA',
                ],
                'content_encoding' => 'aes128gcm',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => User::class,
            'endpoint' => $endpoint,
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('push-subscriptions.destroy'), [
                'endpoint' => $endpoint,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_guest_cannot_store_push_subscription(): void
    {
        $this->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://example.com/push',
            'keys' => [
                'p256dh' => 'key',
                'auth' => 'auth',
            ],
        ])->assertUnauthorized();
    }
}
