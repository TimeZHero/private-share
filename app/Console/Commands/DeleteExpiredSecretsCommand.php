<?php

namespace App\Console\Commands;

use App\Models\PendingUpload;
use App\Models\Secret;
use App\Models\SharedFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class DeleteExpiredSecretsCommand extends Command
{
    protected $signature = 'secrets:cleanup';

    protected $description = 'Delete secrets and shared files older than 30 days, idle pending uploads, and leftover temp files';

    public function handle(): int
    {
        $deletedSecrets = 0;
        Secret::olderThan(Secret::LIFETIME_DAYS)->chunkById(100, function ($secrets) use (&$deletedSecrets) {
            foreach ($secrets as $secret) {
                $secret->delete();
                $deletedSecrets++;
            }
        });
        $this->info("Deleted {$deletedSecrets} expired secrets.");

        $deletedFiles = 0;
        SharedFile::olderThan(Secret::LIFETIME_DAYS)->chunkById(100, function ($files) use (&$deletedFiles) {
            foreach ($files as $file) {
                $file->delete();
                $deletedFiles++;
            }
        });
        $this->info("Deleted {$deletedFiles} expired shared files.");

        $deletedPending = 0;
        PendingUpload::idle()->chunkById(100, function ($pendingUploads) use (&$deletedPending) {
            foreach ($pendingUploads as $pending) {
                @unlink($pending->temp_path);
                $pending->delete();
                $deletedPending++;
            }
        });
        $this->info("Deleted {$deletedPending} idle pending uploads.");

        $deletedTempFiles = $this->deleteOrphanedChunkFiles() + $this->deleteLeftoverDownloadCopies();
        $this->info("Deleted {$deletedTempFiles} leftover temp files.");

        return self::SUCCESS;
    }

    /**
     * Chunk files whose upload row is gone, e.g. after a crash between steps.
     */
    private function deleteOrphanedChunkFiles(): int
    {
        $directory = PendingUpload::temporaryDirectory();

        if (! is_dir($directory)) {
            return 0;
        }

        $files = collect(File::files($directory))
            ->filter(fn (SplFileInfo $file) => $this->isOlderThanIdleWindow($file->getPathname()));
        $liveIds = PendingUpload::query()
            ->whereIn('id', $files->map(fn (SplFileInfo $file) => $file->getFilename())->all())
            ->pluck('id')
            ->all();

        return $files
            ->reject(fn (SplFileInfo $file) => in_array($file->getFilename(), $liveIds, true))
            ->filter(fn (SplFileInfo $file) => @unlink($file->getPathname()))
            ->count();
    }

    /**
     * Local downloads stream a temp copy that is deleted after sending; a crash mid-send leaves it behind.
     */
    private function deleteLeftoverDownloadCopies(): int
    {
        return collect(glob(sys_get_temp_dir().'/'.SharedFile::DOWNLOAD_TEMP_PREFIX.'*') ?: [])
            ->filter(fn (string $path) => $this->isOlderThanIdleWindow($path))
            ->filter(fn (string $path) => @unlink($path))
            ->count();
    }

    private function isOlderThanIdleWindow(string $path): bool
    {
        return filemtime($path) < now()->subMinutes(PendingUpload::IDLE_MINUTES)->getTimestamp();
    }
}
