<?php

namespace App\Console\Commands;

use App\Models\PendingUpload;
use App\Models\Secret;
use App\Models\SharedFile;
use Illuminate\Console\Command;

class DeleteExpiredSecretsCommand extends Command
{
    protected $signature = 'secrets:cleanup';

    protected $description = 'Delete secrets, shared files older than 30 days, and stale pending uploads';

    public function handle(): int
    {
        $deletedSecrets = 0;
        Secret::olderThan(30)->chunkById(100, function ($secrets) use (&$deletedSecrets) {
            foreach ($secrets as $secret) {
                $secret->delete();
                $deletedSecrets++;
            }
        });
        $this->info("Deleted {$deletedSecrets} expired secrets.");

        $deletedFiles = 0;
        SharedFile::olderThan(30)->chunkById(100, function ($files) use (&$deletedFiles) {
            foreach ($files as $file) {
                $file->delete();
                $deletedFiles++;
            }
        });
        $this->info("Deleted {$deletedFiles} expired shared files.");

        $deletedPending = 0;
        PendingUpload::stale(24)->chunkById(100, function ($pendingUploads) use (&$deletedPending) {
            foreach ($pendingUploads as $pending) {
                @unlink($pending->temp_path);
                $pending->delete();
                $deletedPending++;
            }
        });
        $this->info("Deleted {$deletedPending} stale pending uploads.");

        return self::SUCCESS;
    }
}
