<?php

use App\Models\Secret;
use Illuminate\Support\Facades\Log;

test('observer logs secret creation with id', function () {
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Secret created'
                && isset($context['id'])
                && strlen($context['id']) === 12;
        });

    Secret::factory()->create();
});

test('observer logs secret deletion with id and age', function () {
    // Create secret and set it to be 60 seconds old
    $secret = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $secret->id)
        ->update(['created_at' => now()->subSeconds(60)]);

    $secret->refresh();

    // Expect logging on creation (already happened) and deletion
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) use ($secret) {
            return $message === 'Secret deleted'
                && $context['id'] === $secret->id
                && isset($context['age_seconds']);
        });

    $secret->delete();
});

test('deletion log includes accurate age in seconds', function () {
    $secret = Secret::factory()->create();
    $ageInSeconds = 120;

    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $secret->id)
        ->update(['created_at' => now()->subSeconds($ageInSeconds)]);

    $secret->refresh();

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) use ($ageInSeconds) {
            if ($message !== 'Secret deleted') {
                return false;
            }

            // Allow small tolerance for timing
            return abs($context['age_seconds'] - $ageInSeconds) <= 2;
        });

    $secret->delete();
});
