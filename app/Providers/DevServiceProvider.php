<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

/**
 * Processes started by `php artisan dev`, which Sail runs as its container
 * command (SUPERVISOR_PHP_COMMAND in compose.yaml). Defaults already cover the
 * queue, Vite and log tailing.
 */
class DevServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! App::isLocal()) {
            return;
        }

        // Caddy reaches the container on port 80; the default `serve` binds 127.0.0.1:8000.
        DevCommands::artisan('serve --host=0.0.0.0 --port=80', 'server');
        DevCommands::artisan('schedule:work', 'scheduler');
    }
}
