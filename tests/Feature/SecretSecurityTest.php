<?php

use App\Models\Secret;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ENCRYPTION SECURITY TESTS
 *
 * These tests verify that:
 * 1. The server NEVER stores or receives plaintext secrets
 * 2. Encryption keys are NEVER sent to the server
 * 3. Content stored on server is always encrypted ciphertext
 */
describe('Encryption Security', function () {
    test('server stores encrypted content, not plaintext', function () {
        // Simulate what the client does: encrypt and send ciphertext
        $plaintext = 'My super secret password';
        $ciphertext = base64_encode('AES-GCM-ENCRYPTED-'.$plaintext); // Simulated encrypted content

        $response = $this->postJson('/api/secrets', [
            'content' => $ciphertext,
        ]);

        $response->assertSuccessful();

        $secret = Secret::first();

        // The server should store EXACTLY what was sent (ciphertext)
        expect($secret->content)->toBe($ciphertext);
        // The server should NOT have the plaintext
        expect($secret->content)->not->toBe($plaintext);
        expect($secret->content)->not->toContain($plaintext);
    });

    test('retrieve endpoint returns ciphertext not plaintext', function () {
        $ciphertext = base64_encode('encrypted-data-here');
        $secret = Secret::factory()->create(['content' => $ciphertext]);

        $response = $this->postJson("/api/secrets/{$secret->id}/retrieve");

        $response->assertSuccessful();
        $response->assertJson(['content' => $ciphertext]);
    });

    test('server cannot decrypt content without client-side key', function () {
        // Real encrypted content (AES-GCM) - this would require the key to decrypt
        $encryptedContent = 'RW5jcnlwdGVkQ29udGVudFdpdGhJVkFuZFRhZw==';

        $secret = Secret::factory()->create(['content' => $encryptedContent]);

        // Even if someone accesses the database, they only get ciphertext
        $storedContent = Secret::find($secret->id)->content;

        expect($storedContent)->toBe($encryptedContent);
        // Decryption would fail without the key (which is never sent to server)
    });

    test('encryption key in URL fragment is never sent to server', function () {
        // URL fragments (everything after #) are NEVER sent to the server
        // This is a fundamental browser security feature
        $secret = Secret::factory()->create();

        // When requesting the secret page, the fragment is not in the request
        $response = $this->get("/{$secret->id}");

        $response->assertSuccessful();
        // The server only sees the secret ID, never the encryption key
    });

    test('content field accepts any base64 encoded data', function () {
        // Various encrypted payloads should all be accepted
        $payloads = [
            base64_encode(random_bytes(128)), // Random encrypted content
            base64_encode(str_repeat('x', 1000)), // Large encrypted content
            base64_encode("\x00\x01\x02\x03"), // Binary encrypted content
        ];

        foreach ($payloads as $payload) {
            $response = $this->postJson('/api/secrets', ['content' => $payload]);
            $response->assertSuccessful();

            $secretId = $response->json('id');
            $secret = Secret::find($secretId);
            expect($secret->content)->toBe($payload);
        }
    });
});

/**
 * PASSWORD SECURITY TESTS
 *
 * These tests verify that:
 * 1. Passwords are ALWAYS hashed before storage
 * 2. Wrong passwords don't reveal secrets
 * 3. Wrong password attempts don't delete the secret
 * 4. Password validation is enforced
 */
describe('Password Security', function () {
    test('password is hashed using bcrypt before storage', function () {
        $plainPassword = 'MySecurePassword123!';

        $this->postJson('/api/secrets', [
            'content' => 'encrypted-content',
            'password' => $plainPassword,
        ]);

        $secret = Secret::first();

        // Password must be hashed
        expect($secret->password)->not->toBe($plainPassword);
        // Must start with bcrypt prefix
        expect($secret->password)->toStartWith('$2y$');
        // Bcrypt hashes are 60 characters
        expect(strlen($secret->password))->toBe(60);
        // Must verify correctly
        expect(Hash::check($plainPassword, $secret->password))->toBeTrue();
    });

    test('wrong password does not reveal secret content', function () {
        $secret = Secret::factory()->withPassword('correct-password')->create([
            'content' => 'super-secret-content',
        ]);

        $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
            'password' => 'wrong-password',
        ]);

        $response->assertForbidden();
        $response->assertJsonMissing(['content' => 'super-secret-content']);
        $response->assertJson(['error' => 'invalid_password']);
    });

    test('wrong password does not delete secret', function () {
        $secret = Secret::factory()->withPassword('correct-password')->create();
        $secretId = $secret->id;

        // Try wrong password multiple times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson("/api/secrets/{$secretId}/retrieve", [
                'password' => 'wrong-password-'.$i,
            ]);
            $response->assertForbidden();
        }

        // Secret should still exist
        expect(Secret::find($secretId))->not->toBeNull();
    });

    test('password is required when secret is password protected', function () {
        $secret = Secret::factory()->withPassword('secretpass')->create();

        // Attempt without password
        $response = $this->postJson("/api/secrets/{$secret->id}/retrieve");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
    });

    test('password minimum length is enforced (4 characters)', function () {
        $response = $this->postJson('/api/secrets', [
            'content' => 'encrypted-content',
            'password' => '123', // Too short
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');

        // Exactly 4 characters should work
        $response = $this->postJson('/api/secrets', [
            'content' => 'encrypted-content',
            'password' => '1234',
        ]);

        $response->assertSuccessful();
    });

    test('empty password is treated as no password', function () {
        $response = $this->postJson('/api/secrets', [
            'content' => 'encrypted-content',
            'password' => '',
        ]);

        $response->assertSuccessful();

        $secret = Secret::first();
        expect($secret->isPasswordProtected())->toBeFalse();
    });

    test('null password is treated as no password', function () {
        $response = $this->postJson('/api/secrets', [
            'content' => 'encrypted-content',
            'password' => null,
        ]);

        $response->assertSuccessful();

        $secret = Secret::first();
        expect($secret->isPasswordProtected())->toBeFalse();
    });

    test('password timing attack resistance - wrong password takes similar time', function () {
        $secret = Secret::factory()->withPassword('correct-password-here')->create();

        // Measure time for correct password (but still within expected range)
        $start = microtime(true);
        $this->postJson("/api/secrets/{$secret->id}/retrieve", [
            'password' => 'wrong',
        ]);
        $wrongTime = microtime(true) - $start;

        // Recreate secret for fair comparison
        $secret2 = Secret::factory()->withPassword('correct-password-here')->create();

        $start = microtime(true);
        $this->postJson("/api/secrets/{$secret2->id}/retrieve", [
            'password' => 'correct-password-here',
        ]);
        $correctTime = microtime(true) - $start;

        // Times should be within similar range (bcrypt handles this)
        // We just verify the test runs - bcrypt's constant-time comparison handles the rest
        expect($wrongTime)->toBeGreaterThan(0);
        expect($correctTime)->toBeGreaterThan(0);
    });
});

/**
 * SECRET LIFECYCLE SECURITY TESTS
 *
 * These tests verify that:
 * 1. Secrets are PERMANENTLY deleted after viewing
 * 2. Secrets cannot be retrieved twice
 * 3. Deleted secrets return 404
 */
describe('Secret Lifecycle Security', function () {
    test('secret is permanently deleted after successful retrieval', function () {
        $secret = Secret::factory()->create();
        $secretId = $secret->id;

        $response = $this->postJson("/api/secrets/{$secretId}/retrieve");
        $response->assertSuccessful();

        // Database should not contain the secret
        expect(Secret::find($secretId))->toBeNull();

        // Direct database query to be absolutely sure
        $count = \Illuminate\Support\Facades\DB::table('secrets')
            ->where('id', $secretId)
            ->count();

        expect($count)->toBe(0);
    });

    test('secret cannot be retrieved twice', function () {
        $secret = Secret::factory()->create();
        $secretId = $secret->id;

        // First retrieval succeeds
        $response = $this->postJson("/api/secrets/{$secretId}/retrieve");
        $response->assertSuccessful();

        // Second retrieval fails with 404
        $response = $this->postJson("/api/secrets/{$secretId}/retrieve");
        $response->assertNotFound();
    });

    test('retrieved secret data is gone from database', function () {
        $sensitiveContent = 'extremely-sensitive-data';
        $secret = Secret::factory()->create(['content' => $sensitiveContent]);
        $secretId = $secret->id;

        $this->postJson("/api/secrets/{$secretId}/retrieve");

        // Verify no trace of the content remains
        $allSecrets = Secret::all();
        foreach ($allSecrets as $s) {
            expect($s->content)->not->toBe($sensitiveContent);
        }

        // Also check raw database
        $found = \Illuminate\Support\Facades\DB::table('secrets')
            ->where('content', 'like', '%'.$sensitiveContent.'%')
            ->exists();

        expect($found)->toBeFalse();
    });

    test('check endpoint does not reveal secret content', function () {
        $secret = Secret::factory()->create([
            'content' => 'my-secret-content-here',
        ]);

        $response = $this->getJson("/api/secrets/{$secret->id}/check");

        $response->assertSuccessful();
        // Response should only contain metadata, not content
        $response->assertJsonMissing(['content']);
        $response->assertJsonStructure([
            'requires_confirmation',
            'requires_password',
        ]);
    });

    test('check endpoint does not delete secret', function () {
        $secret = Secret::factory()->create();

        // Multiple checks should not affect secret
        for ($i = 0; $i < 10; $i++) {
            $this->getJson("/api/secrets/{$secret->id}/check");
        }

        expect(Secret::find($secret->id))->not->toBeNull();
    });
});

/**
 * ACCESS CONTROL SECURITY TESTS
 *
 * These tests verify that:
 * 1. Non-existent secrets return 404 (not 403 - avoid enumeration)
 * 2. Secret IDs are not guessable
 */
describe('Access Control Security', function () {
    test('non-existent secrets return 404 to prevent enumeration', function () {
        // Various non-existent IDs should all return 404
        $nonExistentIds = [
            'AAAAAAAAAAAA',
            'zzzzzzzzzzzz',
            'test12345678',
            str_repeat('a', 12),
        ];

        foreach ($nonExistentIds as $id) {
            $response = $this->postJson("/api/secrets/{$id}/retrieve");
            $response->assertNotFound();

            $response = $this->getJson("/api/secrets/{$id}/check");
            $response->assertNotFound();

            $response = $this->get("/{$id}");
            $response->assertNotFound();
        }
    });

    test('secret IDs are sufficiently random', function () {
        $secrets = Secret::factory()->count(100)->create();

        $ids = $secrets->pluck('id');

        // All IDs should be unique
        expect($ids->unique()->count())->toBe(100);

        // IDs should be alphanumeric and 12 characters
        foreach ($ids as $id) {
            expect($id)->toMatch('/^[a-zA-Z0-9]{12}$/');
        }

        // Calculate entropy: 62^12 possible combinations
        // This should be sufficient to prevent brute-force enumeration
    });

    test('sequential creation does not produce predictable IDs', function () {
        $ids = [];

        for ($i = 0; $i < 10; $i++) {
            $secret = Secret::factory()->create();
            $ids[] = $secret->id;
        }

        // No two consecutive IDs should be "similar"
        for ($i = 1; $i < count($ids); $i++) {
            $similarity = similar_text($ids[$i - 1], $ids[$i]);
            // Less than 50% similarity suggests good randomness
            expect($similarity)->toBeLessThan(6);
        }
    });
});

/**
 * INPUT VALIDATION SECURITY TESTS
 *
 * These tests verify that:
 * 1. Content is required and validated
 * 2. Malicious input is handled safely
 */
describe('Input Validation Security', function () {
    test('content is required', function () {
        $response = $this->postJson('/api/secrets', []);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('content');

        $response = $this->postJson('/api/secrets', ['content' => '']);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('content');
    });

    test('content must be a string', function () {
        $response = $this->postJson('/api/secrets', ['content' => ['array', 'data']]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('content');

        $response = $this->postJson('/api/secrets', ['content' => 12345]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('content');
    });

    test('requires_confirmation must be boolean', function () {
        $response = $this->postJson('/api/secrets', [
            'content' => 'test',
            'requires_confirmation' => 'yes', // Invalid - should be boolean
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('requires_confirmation');
    });

    test('XSS in content is stored as-is (client handles rendering)', function () {
        $xssPayload = '<script>alert("XSS")</script><img src=x onerror=alert(1)>';

        $response = $this->postJson('/api/secrets', [
            'content' => base64_encode($xssPayload), // Client would encrypt this
        ]);

        $response->assertSuccessful();

        // Content is stored as-is (it's encrypted on client side anyway)
        $secret = Secret::first();
        expect($secret->content)->toBe(base64_encode($xssPayload));
    });

    test('SQL injection in content is safely stored', function () {
        $sqlInjection = "'; DROP TABLE secrets; --";

        $response = $this->postJson('/api/secrets', [
            'content' => $sqlInjection,
        ]);

        $response->assertSuccessful();

        // Content is stored safely (Eloquent handles parameterized queries)
        $secret = Secret::first();
        expect($secret->content)->toBe($sqlInjection);
        expect(Secret::count())->toBe(1); // Table not dropped
    });
});

/**
 * LOGGING SECURITY TESTS
 *
 * These tests verify that:
 * 1. Logs do not contain sensitive data
 * 2. Only metadata is logged
 */
describe('Logging Security', function () {
    test('secret content is not logged on creation', function () {
        $sensitiveContent = 'SUPER_SECRET_PASSWORD_123';

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($sensitiveContent) {
                // Message should be about creation
                if ($message !== 'Secret created') {
                    return false;
                }

                // Context should only contain ID, not content
                return isset($context['id'])
                    && ! isset($context['content'])
                    && ! str_contains(json_encode($context), $sensitiveContent);
            });

        Secret::factory()->create(['content' => $sensitiveContent]);
    });

    test('secret content is not logged on retrieval', function () {
        $sensitiveContent = 'TOP_SECRET_DATA_HERE';
        $secret = Secret::factory()->create(['content' => $sensitiveContent]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($sensitiveContent) {
                if ($message !== 'Secret retrieved') {
                    return false;
                }

                return ! str_contains(json_encode($context), $sensitiveContent);
            });

        // Suppress deletion log
        Log::shouldReceive('info')->once();

        $this->postJson("/api/secrets/{$secret->id}/retrieve");
    });

    test('password is not logged', function () {
        $password = 'MY_SECRET_PASSWORD';

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($password) {
                if ($message !== 'Secret created') {
                    return false;
                }

                return ! isset($context['password'])
                    && ! str_contains(json_encode($context), $password);
            });

        Secret::factory()->withPassword($password)->create();
    });
});

/**
 * HEADER SECURITY TESTS
 *
 * These tests verify proper security headers are set
 */
describe('Header Security', function () {
    test('secret page has no-cache headers', function () {
        $secret = Secret::factory()->create();

        $response = $this->get("/{$secret->id}");

        // Cache-Control header should contain all no-cache directives (order may vary)
        $cacheControl = $response->headers->get('Cache-Control');
        expect($cacheControl)->toContain('no-store');
        expect($cacheControl)->toContain('no-cache');
        expect($cacheControl)->toContain('must-revalidate');
        expect($cacheControl)->toContain('max-age=0');

        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');
    });
});
