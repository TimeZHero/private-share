<?php

namespace Database\Seeders;

use App\Enums\GuestLinkDuration;
use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\URL;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->seedGuestLinks();
    }

    /**
     * One unused link per duration plus an expired one, with fixed ids so a
     * new seed run resets them (each link can only be accepted once).
     */
    private function seedGuestLinks(): void
    {
        $creator = User::firstOrCreate(
            ['email' => 'dev@localhost'],
            [
                'name' => 'Dev User',
                'provider' => 'debug',
                'provider_id' => 'debug-0',
                'email_verified_at' => now(),
            ],
        );

        $guestLinks = [];

        foreach (GuestLinkDuration::cases() as $duration) {
            $guestLinks["{$duration->value}-day"] = [
                'id' => str_pad("seeded-guest-{$duration->value}d", 32, '0'),
                'expires_at' => $duration->expiresAt(),
            ];
        }

        $guestLinks['expired'] = [
            'id' => str_pad('seeded-guest-expired', 32, '0'),
            'expires_at' => now()->subHour(),
        ];

        foreach ($guestLinks as $label => $attributes) {
            $guestLink = GuestLink::query()->firstOrNew(['id' => $attributes['id']]);
            $guestLink->forceFill([
                'user_id' => $creator->id,
                'expires_at' => $attributes['expires_at'],
                'used_at' => null,
                'access_token_hash' => null,
            ])->save();

            $this->command?->info("Guest link ({$label}): ".URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]));
        }
    }
}
