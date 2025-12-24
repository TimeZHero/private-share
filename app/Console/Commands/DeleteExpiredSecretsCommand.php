<?php

namespace App\Console\Commands;

use App\Models\Secret;
use Illuminate\Console\Command;

class DeleteExpiredSecretsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'secrets:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete secrets older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = Secret::olderThan(30)->get()->each->delete();

        $this->info("Deleted {$deleted->count()} expired secrets.");

        return self::SUCCESS;
    }
}
