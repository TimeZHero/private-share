<?php

namespace App\Services;

use App\Exceptions\UploadQuotaExceeded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Caps the bytes each uploader (user or guest link) may start uploading per
 * 24-hour window. Bytes are reserved when an upload starts, so abandoned
 * uploads still count.
 */
final class UploadQuota
{
    private const int WINDOW_SECONDS = 86400;

    /**
     * @throws UploadQuotaExceeded
     */
    public function reserve(string $uploader, int $bytes): void
    {
        $quotaGb = (int) config('features.file_daily_quota_gb');

        if ($quotaGb === 0) {
            return;
        }

        $key = "upload-quota:{$uploader}";
        $usedBytes = RateLimiter::attempts($key);

        if ($usedBytes + $bytes > $quotaGb * 1024 * 1024 * 1024) {
            Log::warning('Upload quota exceeded', ['used_bytes' => $usedBytes, 'requested_bytes' => $bytes]);

            throw new UploadQuotaExceeded($quotaGb, max(RateLimiter::availableIn($key), 1));
        }

        RateLimiter::increment($key, self::WINDOW_SECONDS, $bytes);
    }
}
