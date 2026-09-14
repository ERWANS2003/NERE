<?php

namespace Database\Factories;

use App\Models\DashboardNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DashboardNotification>
 */
class DashboardNotificationFactory extends Factory
{
    protected $model = DashboardNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['ticket_created', 'ticket_assigned', 'sla_warning', 'sla_breached', 'comment_added', 'incident_created'];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->sentence(10),
            'icon' => $this->faker->randomElement(['🎫', '📋', '⏰', '🚨', '💬', '⚠️']),
            'color' => $this->faker->randomElement(['blue', 'primary', 'yellow', 'red', 'info', 'success', 'danger']),
            'action_url' => $this->faker->url(),
            'data' => [],
            'read_at' => null,
        ];
    }

    /**
     * Mark notification as unread
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
