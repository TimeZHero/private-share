<?php

namespace Database\Factories;

use App\Enums\GuestLinkDuration;
use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestLink>
 */
class GuestLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expires_at' => GuestLinkDuration::OneDay->expiresAt(),
        ];
    }

    public function lasting(GuestLinkDuration $duration): static
    {
        return $this->state(fn () => [
            'expires_at' => $duration->expiresAt(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
