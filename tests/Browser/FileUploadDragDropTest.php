<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true, 'features.file_uploads' => true]);
    $this->actingAs(User::factory()->create());
});

describe('File Upload Drag & Drop', function () {
    it('shows the drop zone when file uploads are enabled', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->assertSee('Drag & drop a file or')
            ->assertSee('Browse');
    });

    it('does not show the drop zone when file uploads are disabled', function () {
        Feature::purge(FileUploads::class);
        config(['features.file_uploads' => false]);

        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->assertDontSee('Drag & drop a file or');
    });

    it('shows full-window overlay when a file is dragged over the window', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->assertDontSee('Drop your file to attach');

        $page->script(<<<'JS'
            const event = new DragEvent('dragenter', {
                bubbles: true,
                cancelable: true,
                dataTransfer: new DataTransfer()
            });
            event.dataTransfer.items.add(new File([''], 'test.txt', { type: 'text/plain' }));
            window.dispatchEvent(event);
        JS);

        $page->waitForText('Drop your file to attach')
            ->assertSee('The file will be encrypted end-to-end');
    });

    it('hides the overlay when the file drag leaves the window', function () {
        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]');

        $page->script(<<<'JS'
            const enterEvent = new DragEvent('dragenter', {
                bubbles: true,
                cancelable: true,
                dataTransfer: new DataTransfer()
            });
            enterEvent.dataTransfer.items.add(new File([''], 'test.txt', { type: 'text/plain' }));
            window.dispatchEvent(enterEvent);
        JS);

        $page->waitForText('Drop your file to attach');

        $page->script(<<<'JS'
            const leaveEvent = new DragEvent('dragleave', {
                bubbles: true,
                cancelable: true,
                dataTransfer: new DataTransfer()
            });
            leaveEvent.dataTransfer.items.add(new File([''], 'test.txt', { type: 'text/plain' }));
            window.dispatchEvent(leaveEvent);
        JS);

        $page->wait(1)
            ->assertDontSee('Drop your file to attach');
    });

    it('has no JavaScript errors on the home page with file uploads enabled', function () {
        $page = visit('/');

        $page->assertNoJavascriptErrors();
    });
});
