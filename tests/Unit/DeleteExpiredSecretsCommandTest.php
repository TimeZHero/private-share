<?php

use App\Models\Secret;
use Illuminate\Support\Facades\Log;

test('command has correct signature and description', function () {
    $command = app(\App\Console\Commands\DeleteExpiredSecretsCommand::class);

    expect($command->getName())->toBe('secrets:cleanup');
    expect($command->getDescription())->toBe('Delete secrets older than 30 days');
});

test('command deletes only expired secrets (30+ days old)', function () {
    // 31 days old - should be deleted
    $expired31 = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $expired31->id)
        ->update(['created_at' => now()->subDays(31)]);

    // 45 days old - should be deleted
    $expired45 = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $expired45->id)
        ->update(['created_at' => now()->subDays(45)]);

    // 29 days old - should NOT be deleted
    $notExpired = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $notExpired->id)
        ->update(['created_at' => now()->subDays(29)]);

    // Brand new - should NOT be deleted
    $brandNew = Secret::factory()->create();

    expect(Secret::count())->toBe(4);

    $this->artisan('secrets:cleanup')
        ->expectsOutputToContain('Deleted 2 expired secrets')
        ->assertSuccessful();

    expect(Secret::count())->toBe(2);
    expect(Secret::find($expired31->id))->toBeNull();
    expect(Secret::find($expired45->id))->toBeNull();
    expect(Secret::find($notExpired->id))->not->toBeNull();
    expect(Secret::find($brandNew->id))->not->toBeNull();
});

test('command returns success exit code', function () {
    $this->artisan('secrets:cleanup')->assertExitCode(0);
});

test('command triggers observer deletion logs', function () {
    $secret = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $secret->id)
        ->update(['created_at' => now()->subDays(31)]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn ($msg) => $msg === 'Secret deleted');

    $this->artisan('secrets:cleanup')->assertSuccessful();
});

test('command handles large batch of expired secrets', function () {
    // Create 50 expired secrets
    $expiredSecrets = Secret::factory()->count(50)->create();

    foreach ($expiredSecrets as $secret) {
        \Illuminate\Support\Facades\DB::table('secrets')
            ->where('id', $secret->id)
            ->update(['created_at' => now()->subDays(35)]);
    }

    // Create 10 valid secrets
    Secret::factory()->count(10)->create();

    expect(Secret::count())->toBe(60);

    $this->artisan('secrets:cleanup')
        ->expectsOutputToContain('Deleted 50 expired secrets')
        ->assertSuccessful();

    expect(Secret::count())->toBe(10);
});
