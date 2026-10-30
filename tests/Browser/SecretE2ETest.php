<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\Secret;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => false, 'features.file_uploads' => false]);
});

/**
 * END-TO-END BROWSER TESTS
 *
 * These tests use Playwright and require:
 * - npm install playwright@latest
 * - npx playwright install
 * - npm run build (React app must be compiled)
 */
describe('Secret Creation E2E', function () {
    it('loads the home page correctly', function () {
        $page = visit('/');

        $page->assertSee('Share Secret')
            ->assertSee('Secrets are deleted after being viewed once')
            ->assertSee('Unretrieved secrets auto-expire after 30 days');
    });

    it('shows a plain and markdown mode switch by default', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->assertSee('Plain')
            ->assertSee('Markdown')
            ->assertDontSee('Editor')
            ->assertDontSee('Preview');
    });

    it('displays markdown editor and preview when markdown is enabled', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->click('Markdown')
            ->waitForText('Editor')
            ->assertSee('Editor')
            ->assertSee('Preview');
    });

    it('returns to a plain editor when markdown is switched off', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->click('Markdown')
            ->waitForText('Editor')
            ->click('Plain')
            ->assertDontSee('Editor')
            ->assertDontSee('Preview');
    });

    it('shows security options', function () {
        $page = visit('/');

        $page->assertSee('Require password');
    });

    it('password input does not trigger password manager save prompts', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->click('Require password')
            ->assertAttribute('input[type="password"]', 'autocomplete', 'one-time-code');
    });

    it('creates a secret and shows success view with link', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->fill('#content', 'My secret message')
            ->click('Share Secret')
            ->waitForText('Secret Created!')
            ->assertSee('The encryption key after # is required to decrypt');

        expect(Secret::count())->toBe(1);
    });

    it('validates empty content', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->click('Share Secret')
            ->waitForText('Please enter some content');
    });

    it('creates secret with encrypted content in database - key never sent to server', function () {
        $page = visit('/');

        $plaintext = 'This is my super secret message that should be encrypted';

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->fill('#content', $plaintext)
            ->click('Share Secret')
            ->waitForText('Secret Created!');

        $secret = Secret::first();
        expect($secret)->not->toBeNull();

        expect($secret->content)->not->toBe($plaintext);
        expect($secret->content)->not->toContain($plaintext);

        expect(base64_decode($secret->content, true))->not->toBeFalse();

        expect(strlen($secret->content))->toBeGreaterThan(strlen($plaintext));
    });
});

describe('Secret Retrieval E2E', function () {
    it('shows 404 for non-existent secret', function () {
        $page = visit('/AAAAAAAAAAAA');

        $page->assertSee('404')
            ->assertSee('Not found');
    });

    it('loads the secret page for a valid secret', function () {
        $secret = Secret::factory()->create();

        $page = visit("/{$secret->id}");

        $page->waitForText('View Secret')
            ->assertSee('View Secret')
            ->assertSee('Secrets are deleted after being viewed once');
    });

    it('shares with a 128-bit key and decrypts the link it produced', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->fill('#content', 'Round trip with a strong key')
            ->click('Share Secret')
            ->waitForText('Secret Created!');

        $link = $page->value('input[readonly]');
        expect($link)->toMatch('/#[A-Za-z0-9_-]{22}$/');

        // visit() drops URL fragments, so the key goes through the retry field instead.
        visit(parse_url($link, PHP_URL_PATH))
            ->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->waitForText('View Secret')
            ->click('View Secret')
            ->waitForText('Decryption Failed')
            ->fill('input[placeholder^="Paste the full link"]', $link)
            ->click('Retry Decryption')
            ->waitForText('Round trip with a strong key');
    });

    it('still decrypts links created with the legacy 8-character key', function () {
        $legacyKey = 'Ab3xK9mZ';
        $salt = random_bytes(16);
        $iv = random_bytes(12);
        $derivedKey = hash_pbkdf2('sha256', $legacyKey, $salt, 100000, 32, true);
        $ciphertext = openssl_encrypt('Legacy link content', 'aes-256-gcm', $derivedKey, OPENSSL_RAW_DATA, $iv, $tag);

        $secret = Secret::factory()->create([
            'content' => base64_encode($salt.$iv.$ciphertext.$tag),
        ]);

        visit("/{$secret->id}")
            ->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->waitForText('View Secret')
            ->click('View Secret')
            ->waitForText('Decryption Failed')
            ->fill('input[placeholder^="Paste the full link"]', $legacyKey)
            ->click('Retry Decryption')
            ->waitForText('Legacy link content');
    });

    it('tells the recipient how long to wait after too many wrong passwords', function () {
        $secret = Secret::factory()->withPassword('correct horse')->create();

        $page = visit("/{$secret->id}")
            ->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->waitForText('Password Required');

        foreach (range(1, 5) as $attempt) {
            $page->fill('input[type="password"]', "wrong {$attempt}")
                ->click('View Secret')
                ->waitForText('The password is incorrect.');
        }

        $page->fill('input[type="password"]', 'wrong 6')
            ->click('View Secret')
            ->waitForText('Too many wrong passwords. Try again in 1 minute.');
    });

    it('shows decryption error when encryption key is missing from URL', function () {
        $secret = Secret::factory()->create();

        $page = visit("/{$secret->id}");

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->waitForText('View Secret')
            ->click('View Secret')
            ->waitForText('Decryption Failed');
    });
});

describe('Security - Console Monitoring', function () {
    it('ensures home page has no JavaScript errors', function () {
        $page = visit('/');

        $page->assertNoJavascriptErrors();
    });

    it('ensures home page has no console warnings or errors', function () {
        $page = visit('/');

        $page->assertNoConsoleLogs(['warning', 'error']);
    });

    it('ensures secret page has no JavaScript errors', function () {
        $secret = Secret::factory()->create();

        $page = visit("/{$secret->id}");

        $page->assertNoJavascriptErrors();
    });
});

describe('Smoke Tests', function () {
    it('loads home page without errors', function () {
        $page = visit('/');

        $page->assertNoJavascriptErrors()
            ->assertNoConsoleLogs(['error']);
    });

    it('loads secret page without errors', function () {
        $secret = Secret::factory()->create();

        $page = visit("/{$secret->id}");

        $page->assertNoJavascriptErrors();
    });
});
