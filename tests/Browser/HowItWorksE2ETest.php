<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.file_uploads' => false]);
});

describe('How It Works Modal E2E', function () {
    it('opens the modal from the account menu when auth is enabled', function () {
        config(['features.auth' => true]);

        $this->actingAs(User::factory()->create());

        $page = visit('/');

        $page->assertDontSee('Sharing a secret')
            ->assertMissing('[aria-label="How it works"]')
            ->click('[aria-label="Account menu"]')
            ->click('How it works')
            ->waitForText('Sharing a secret')
            ->assertSee('Burn after reading')
            ->assertSee('Only you and the recipient can read it')
            ->assertSee('Create a guest link')
            ->assertDontSee('#key')
            ->click('[aria-label="Close"]')
            ->assertDontSee('Sharing a secret');
    });

    it('exposes a standalone topbar trigger when auth is disabled', function () {
        config(['features.auth' => false]);

        $page = visit('/');

        $page->assertMissing('[aria-label="Account menu"]')
            ->click('[aria-label="How it works"]')
            ->waitForText('Sharing a secret')
            ->assertSee('Burn after reading')
            ->assertDontSee('Create a guest link');
    });

    it('shows the support help text when configured', function () {
        config([
            'features.auth' => false,
            'support.help_text' => 'Having any issues? Let us know at devops@caffeina.com',
        ]);

        $page = visit('/');

        $page->click('[aria-label="How it works"]')
            ->waitForText('Sharing a secret')
            ->assertSee('Having any issues? Let us know at devops@caffeina.com');
    });

    it('omits the support help section when not configured', function () {
        config(['features.auth' => false, 'support.help_text' => null]);

        $page = visit('/');

        $page->click('[aria-label="How it works"]')
            ->waitForText('Sharing a secret')
            ->assertDontSee('Having any issues?');
    });
});
