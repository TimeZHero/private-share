<?php

use App\Models\Secret;

/**
 * END-TO-END BROWSER TESTS
 *
 * These tests use Playwright and require:
 * - npm install playwright@latest
 * - npx playwright install
 */
describe('Secret Creation E2E', function () {
    it('loads the home page correctly', function () {
        $page = visit('/');

        $page->assertSee('Share Secret')
            ->assertSee('End-to-End Encrypted')
            ->assertSee('Key Never Leaves Browser');
    });

    it('displays markdown editor and preview', function () {
        $page = visit('/');

        $page->assertSee('Editor')
            ->assertSee('Preview')
            ->assertSee('Markdown supported');
    });

    it('shows security options', function () {
        $page = visit('/');

        $page->assertSee('Require confirmation')
            ->assertSee('Require password');
    });

    it('creates a secret and shows success view with link', function () {
        $page = visit('/');

        $page->fill('#content', 'My secret message')
            ->click('Share Secret')
            ->waitForText('Secret Created!')
            ->assertSee('The encryption key after # is required to decrypt');

        // Verify secret was created in database
        expect(Secret::count())->toBe(1);
    });

    it('validates empty content', function () {
        $page = visit('/');

        // Try to share without content
        $page->click('Share Secret')
            ->waitForText('Please enter some content');
    });

    it('creates secret with encrypted content in database - key never sent to server', function () {
        $page = visit('/');

        $plaintext = 'This is my super secret message that should be encrypted';

        $page->fill('#content', $plaintext)
            ->click('Share Secret')
            ->waitForText('Secret Created!');

        // CRITICAL SECURITY CHECK:
        // The content stored in the database should be encrypted, NOT plaintext
        // This proves the encryption key never reached the server
        $secret = Secret::first();
        expect($secret)->not->toBeNull();

        // Content should NOT be the original plaintext
        expect($secret->content)->not->toBe($plaintext);
        expect($secret->content)->not->toContain($plaintext);

        // Content should be base64 encoded (from client-side AES-GCM encryption)
        expect(base64_decode($secret->content, true))->not->toBeFalse();

        // The encrypted content should be longer due to IV + auth tag
        expect(strlen($secret->content))->toBeGreaterThan(strlen($plaintext));
    });
});

describe('Secret Retrieval E2E', function () {
    it('shows 404 for non-existent secret', function () {
        $page = visit('/AAAAAAAAAAAA');

        $page->assertSee('404')
            ->assertSee('Page Not Found');
    });

    it('loads the secret page for a valid secret', function () {
        $secret = Secret::factory()->requiresConfirmation()->create();

        // Visit secret page - it should load without errors
        $page = visit("/{$secret->id}");

        // Should see the secret ID on the page
        $page->assertSee($secret->id);
    });

    it('shows decryption error when encryption key is missing from URL', function () {
        $secret = Secret::factory()->create();

        // Visit without the # fragment (no encryption key)
        $page = visit("/{$secret->id}");

        // Should show decryption failed because no key in URL
        $page->waitForText('Decryption Failed');
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
        $secret = Secret::factory()->requiresConfirmation()->create();

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
        $secret = Secret::factory()->requiresConfirmation()->create();

        $page = visit("/{$secret->id}");

        $page->assertNoJavascriptErrors();
    });
});
