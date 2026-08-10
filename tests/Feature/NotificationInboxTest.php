<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\User;
use App\Notifications\PanicAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications(): void
    {
        [$guard, $area, $admin] = $this->guardAndAdmin();

        Http::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('panic.store'))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
            'type' => PanicAlertNotification::class,
        ]);

        $this->actingAs($admin)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');

        $notificationId = $admin->notifications()->firstOrFail()->id;

        $this->actingAs($admin)
            ->postJson(route('notifications.read', $notificationId))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($admin->fresh()->notifications()->firstOrFail()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        [$guard, $area, $admin] = $this->guardAndAdmin();

        Http::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('panic.store'));

        PanicAlert::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
        ]);

        $admin->notify(new PanicAlertNotification(
            PanicAlert::query()->latest('id')->firstOrFail(),
        ));

        $this->assertSame(2, $admin->fresh()->unreadNotifications()->count());

        $this->actingAs($admin)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    /**
     * @return array{0: User, 1: Area, 2: User}
     */
    private function guardAndAdmin(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $admin = User::factory()->create();

        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        User::factory()->create([
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
        ])->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        return [$guard, $area, $admin];
    }
}
