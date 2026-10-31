<?php

use App\Models\Secret;

function guessPassword(Secret $secret, string $password, string $clientIp = '93.184.216.34')
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $clientIp])
        ->postJson("/api/secrets/{$secret->id}/retrieve", ['password' => $password]);
}

describe('per-client backoff', function () {
    test('five wrong passwords are free, the sixth locks the client out for a minute', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();

        foreach (range(1, 5) as $attempt) {
            guessPassword($secret, "wrong {$attempt}")->assertForbidden();
        }

        guessPassword($secret, 'wrong 6')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After', 60)
            ->assertJson([
                'error' => 'too_many_wrong_passwords',
                'retry_after' => 60,
                'message' => 'Too many wrong passwords. Try again in 1 minute.',
            ]);
    });

    test('a locked-out client cannot retrieve even with the right password', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 6) as $attempt) {
            guessPassword($secret, "wrong {$attempt}");
        }

        guessPassword($secret, 'correct horse')->assertTooManyRequests();

        expect(Secret::find($secret->id))->not->toBeNull();
    });

    test('each further failure doubles the lockout', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 6) as $attempt) {
            guessPassword($secret, "wrong {$attempt}");
        }

        $this->travel(61)->seconds();
        guessPassword($secret, 'wrong 7')->assertTooManyRequests()->assertJson(['retry_after' => 120]);

        $this->travel(121)->seconds();
        guessPassword($secret, 'wrong 8')->assertTooManyRequests()->assertJson(['retry_after' => 240]);
    });

    test('the lockout never exceeds one day', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        $retryAfter = 0;

        foreach (range(1, 18) as $attempt) {
            $this->travel($retryAfter + 1)->seconds();
            $retryAfter = guessPassword($secret, "wrong {$attempt}")->json('retry_after') ?? 0;
        }

        expect($retryAfter)->toBe(86400);
    });

    test('a successful retrieval does not reset the failure count', function () {
        $target = Secret::factory()->withPassword('correct horse')->create();
        $ownSecret = Secret::factory()->withPassword('my own')->create();
        foreach (range(1, 5) as $attempt) {
            guessPassword($target, "wrong {$attempt}");
        }

        guessPassword($ownSecret, 'my own')->assertSuccessful();

        guessPassword($target, 'wrong 6')->assertTooManyRequests();
    });

    test('other clients are not affected by one client lockout', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 6) as $attempt) {
            guessPassword($secret, "wrong {$attempt}");
        }

        guessPassword($secret, 'correct horse', clientIp: '151.101.1.69')->assertSuccessful();
    });

    test('IPv6 addresses in the same /64 share one backoff', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 5) as $attempt) {
            guessPassword($secret, "wrong {$attempt}", clientIp: "2001:db8:abcd:12::{$attempt}");
        }

        guessPassword($secret, 'wrong 6', clientIp: '2001:db8:abcd:12:ffff::9')->assertTooManyRequests();
    });
});

describe('per-secret cap', function () {
    test('the tenth wrong password from any clients locks the secret for the hour', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 9) as $client) {
            guessPassword($secret, "wrong {$client}", clientIp: "151.101.1.{$client}")->assertForbidden();
        }
        guessPassword($secret, 'wrong 10', clientIp: '151.101.1.10')->assertTooManyRequests();

        $response = guessPassword($secret, 'correct horse', clientIp: '151.101.2.1')
            ->assertTooManyRequests()
            ->assertJson(['error' => 'secret_locked']);

        expect($response->json('retry_after'))->toBeLessThanOrEqual(3600)
            ->and($response->json('message'))->toStartWith('This secret is temporarily locked after too many wrong passwords. Try again in ')
            ->and(Secret::find($secret->id))->not->toBeNull();
    });

    test('the recipient can retrieve once the window has passed', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();
        foreach (range(1, 10) as $client) {
            guessPassword($secret, "wrong {$client}", clientIp: "151.101.1.{$client}");
        }

        $this->travel(3601)->seconds();

        guessPassword($secret, 'correct horse', clientIp: '151.101.2.1')->assertSuccessful();
    });

    test('secrets without a password are never locked', function () {
        $secret = Secret::factory()->create();
        foreach (range(1, 6) as $attempt) {
            guessPassword(Secret::factory()->withPassword('correct horse')->create(), "wrong {$attempt}");
        }

        $this->withServerVariables(['REMOTE_ADDR' => '93.184.216.34'])
            ->postJson("/api/secrets/{$secret->id}/retrieve")
            ->assertSuccessful();
    });
});

test('a secret already burned by a concurrent request answers 404', function () {
    $secret = Secret::factory()->create();

    $this->postJson("/api/secrets/{$secret->id}/retrieve")->assertSuccessful();
    $this->postJson("/api/secrets/{$secret->id}/retrieve")->assertNotFound();
});
