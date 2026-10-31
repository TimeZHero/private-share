<?php

namespace App\Services;

use App\Exceptions\PasswordAttemptsExceeded;
use App\Models\Secret;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Two independent brakes on password guessing: an exponential lockout per
 * client, and a cap per secret that also stops attackers rotating clients.
 */
final class SecretPasswordAttempts
{
    private const int FREE_FAILURES_PER_CLIENT = 5;

    private const int FIRST_LOCKOUT_SECONDS = 60;

    private const int MAX_LOCKOUT_SECONDS = 86400;

    private const int CLIENT_MEMORY_SECONDS = 86400;

    private const int MAX_FAILURES_PER_SECRET = 10;

    private const int SECRET_WINDOW_SECONDS = 3600;

    /**
     * @throws PasswordAttemptsExceeded
     */
    public function ensureAllowed(string $client, Secret $secret): void
    {
        if (RateLimiter::tooManyAttempts($this->clientLockKey($client), 1)) {
            throw PasswordAttemptsExceeded::forClient(RateLimiter::availableIn($this->clientLockKey($client)));
        }

        if (RateLimiter::tooManyAttempts($this->secretKey($secret), self::MAX_FAILURES_PER_SECRET)) {
            throw PasswordAttemptsExceeded::forSecret(RateLimiter::availableIn($this->secretKey($secret)));
        }
    }

    /**
     * @throws PasswordAttemptsExceeded when this failure starts a lockout
     */
    public function recordFailure(string $client, Secret $secret): void
    {
        RateLimiter::hit($this->secretKey($secret), self::SECRET_WINDOW_SECONDS);

        $clientFailures = $this->countClientFailure($client);

        if ($clientFailures > self::FREE_FAILURES_PER_CLIENT) {
            $lockoutSeconds = (int) min(
                self::FIRST_LOCKOUT_SECONDS * 2 ** ($clientFailures - self::FREE_FAILURES_PER_CLIENT - 1),
                self::MAX_LOCKOUT_SECONDS,
            );

            RateLimiter::clear($this->clientLockKey($client));
            RateLimiter::hit($this->clientLockKey($client), $lockoutSeconds);

            Log::warning('Secret password lockout', [
                'secret_id' => $secret->id,
                'client' => $client,
                'failures' => $clientFailures,
                'lockout_seconds' => $lockoutSeconds,
            ]);
        }

        $this->ensureAllowed($client, $secret);
    }

    /**
     * Sliding memory: every failure pushes the expiry out, so a patient
     * attacker never earns the free attempts back while still guessing.
     * Successful retrievals deliberately do not reset it.
     */
    private function countClientFailure(string $client): int
    {
        $key = "secret-password:failures:{$client}";

        Cache::add($key, 0, self::CLIENT_MEMORY_SECONDS);
        $failures = (int) Cache::increment($key);
        Cache::touch($key, self::CLIENT_MEMORY_SECONDS);

        return $failures;
    }

    private function clientLockKey(string $client): string
    {
        return "secret-password:lockout:{$client}";
    }

    private function secretKey(Secret $secret): string
    {
        return "secret-password:secret:{$secret->id}";
    }
}
