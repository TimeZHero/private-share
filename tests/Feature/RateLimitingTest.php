<?php

use App\Models\Secret;
use Illuminate\Support\Facades\RateLimiter;

describe('Rate Limiting Security', function () {
    beforeEach(function () {
        // Clear rate limiters before each test
        RateLimiter::clear('secrets');
        RateLimiter::clear('secret-password');
    });

    test('secrets API is protected by rate limiter', function () {
        // Make requests up to the limit (30 per minute)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/secrets', [
                'content' => 'test-content-'.$i,
            ]);
            $response->assertSuccessful();
        }

        // 31st request should be rate limited
        $response = $this->postJson('/api/secrets', [
            'content' => 'rate-limited-content',
        ]);

        $response->assertStatus(429); // Too Many Requests
    });

    test('check endpoint is rate limited', function () {
        $secret = Secret::factory()->create();

        // Make requests up to the limit
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson("/api/secrets/{$secret->id}/check");
            $response->assertSuccessful();
        }

        // Next request should be rate limited
        $response = $this->getJson("/api/secrets/{$secret->id}/check");
        $response->assertStatus(429);
    });

    test('retrieve endpoint is rate limited', function () {
        // Create enough secrets for the test
        // The retrieve endpoint has stricter rate limiting: 20 requests/minute per IP
        $secrets = Secret::factory()->count(21)->create();

        // Retrieve secrets up to the limit
        for ($i = 0; $i < 20; $i++) {
            $response = $this->postJson("/api/secrets/{$secrets[$i]->id}/retrieve");
            $response->assertSuccessful();
        }

        // 21st request should be rate limited
        $response = $this->postJson("/api/secrets/{$secrets[20]->id}/retrieve");
        $response->assertStatus(429);
    });

    test('rate limit is per IP address', function () {
        // Make 30 requests from IP 1
        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
                ->postJson('/api/secrets', ['content' => 'test']);
        }

        // 31st request from IP 1 should be blocked
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->postJson('/api/secrets', ['content' => 'test']);
        $response->assertStatus(429);

        // But request from different IP should work
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2'])
            ->postJson('/api/secrets', ['content' => 'test']);
        $response->assertSuccessful();
    });

    test('rate limit prevents password brute force', function () {
        $secret = Secret::factory()->withPassword('correct-password')->create();

        // Attacker tries to brute force password
        // The stricter per-secret rate limit is 5 attempts/minute per secret per IP
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
                'password' => 'wrong-password-'.$i,
            ]);
            $response->assertForbidden();
        }

        // After 5 attempts on the same secret, they get rate limited
        $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
            'password' => 'another-wrong-password',
        ]);
        $response->assertStatus(429);

        // Even with correct password, they're rate limited
        $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
            'password' => 'correct-password',
        ]);
        $response->assertStatus(429);
    });

    test('rate limit prevents secret ID enumeration', function () {
        // Attacker tries to enumerate secret IDs
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson('/api/secrets/'.str_pad($i, 12, '0', STR_PAD_LEFT).'/check');
            // Most will be 404 (not found)
        }

        // After 30 attempts, they get rate limited
        $response = $this->getJson('/api/secrets/AAAAAAAAAAAA/check');
        $response->assertStatus(429);
    });

    test('rate limit response includes retry-after header', function () {
        // Exhaust rate limit
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/secrets', ['content' => 'test']);
        }

        $response = $this->postJson('/api/secrets', ['content' => 'test']);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    });
});
