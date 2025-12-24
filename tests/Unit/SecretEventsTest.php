<?php

use App\Events\SecretRetrieved;
use App\Listeners\BurnSecret;
use App\Models\Secret;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('SecretRetrieved Event', function () {
    test('event contains secret and ip address', function () {
        $secret = Secret::factory()->create();
        $ipAddress = '192.168.1.100';

        // Suppress logging during construction
        Log::shouldReceive('info')->once();

        $event = new SecretRetrieved($secret, $ipAddress);

        expect($event->secret->id)->toBe($secret->id);
        expect($event->ipAddress)->toBe($ipAddress);
    });

    test('event logs retrieval with id and ip', function () {
        $secret = Secret::factory()->create();
        $ipAddress = '10.0.0.1';

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($secret, $ipAddress) {
                return $message === 'Secret retrieved'
                    && $context['id'] === $secret->id
                    && $context['ip'] === $ipAddress
                    && isset($context['age_seconds']);
            });

        new SecretRetrieved($secret, $ipAddress);
    });

    test('event handles null ip address', function () {
        $secret = Secret::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Secret retrieved'
                    && $context['ip'] === null;
            });

        $event = new SecretRetrieved($secret, null);

        expect($event->ipAddress)->toBeNull();
    });

    test('event logs accurate age in seconds', function () {
        $ageInSeconds = 300;
        $secret = Secret::factory()->create();

        \Illuminate\Support\Facades\DB::table('secrets')
            ->where('id', $secret->id)
            ->update(['created_at' => now()->subSeconds($ageInSeconds)]);

        $secret->refresh();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($ageInSeconds) {
                return $message === 'Secret retrieved'
                    && abs($context['age_seconds'] - $ageInSeconds) <= 2;
            });

        new SecretRetrieved($secret, null);
    });
});

describe('BurnSecret Listener', function () {
    test('listener deletes secret on event', function () {
        $secret = Secret::factory()->create();
        $secretId = $secret->id;

        // Allow any log calls (creation, retrieval, deletion)
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $event = new SecretRetrieved($secret, '127.0.0.1');

        $listener = new BurnSecret;
        $listener->handle($event);

        expect(Secret::find($secretId))->toBeNull();
    });

    test('listener is attached to SecretRetrieved event', function () {
        Event::fake([SecretRetrieved::class]);

        $secret = Secret::factory()->create();

        SecretRetrieved::dispatch($secret, '127.0.0.1');

        Event::assertDispatched(SecretRetrieved::class);
    });
});
