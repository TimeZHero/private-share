<?php

namespace App\Observers;

use App\Models\SharedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SharedFileObserver
{
    public function created(SharedFile $sharedFile): void
    {
        Log::info('SharedFile created', [
            'id' => $sharedFile->id,
            'original_name' => $sharedFile->original_name,
            'size' => $sharedFile->size,
        ]);
    }

    public function deleted(SharedFile $sharedFile): void
    {
        Storage::disk(config('features.file_disk'))->delete($sharedFile->storage_path);

        Log::info('SharedFile deleted', [
            'id' => $sharedFile->id,
            'age_seconds' => $sharedFile->created_at->diffInSeconds(now()),
        ]);
    }
}
