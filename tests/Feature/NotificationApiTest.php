<?php

namespace Tests\Feature;

use App\Models\DashboardNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_notifications(): void
    {
        $notifications = DashboardNotification::factory()
            ->count(5)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard-notifications');

        $response->assertOk();
        $response->assertJsonStructure([
            'unread_count',
            'notifications' => [
                '*' => ['id', 'type', 'title', 'message', 'icon', 'color', 'action_url', 'created_at']
            ]
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = DashboardNotification::factory()
            ->for($this->user)
            ->unread()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/dashboard-notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertTrue($notification->fresh()->read_at !== null);
    }

    public function test_user_can_delete_notification(): void
    {
        $notification = DashboardNotification::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/dashboard-notifications/{$notification->id}");

        $response->assertOk();
        $this->assertNull(DashboardNotification::find($notification->id));
    }

    public function test_user_cannot_manage_other_users_notifications(): void
    {
        $otherUser = User::factory()->create();
        $notification = DashboardNotification::factory()
            ->for($otherUser)
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/dashboard-notifications/{$notification->id}/read");

        $response->assertForbidden();
    }
}
