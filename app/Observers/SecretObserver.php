<?php

namespace App\Observers;

use App\Models\Secret;
use Illuminate\Support\Facades\Log;

class SecretObserver
{
    /**
     * Handle the Secret "created" event.
     */
    public function created(Secret $secret): void
    {
        Log::info('Secret created', [
            'id' => $secret->id,
        ]);
    }

    /**
     * Handle the Secret "deleted" event.
     */
    public function deleted(Secret $secret): void
    {
        Log::info('Secret deleted', [
            'id' => $secret->id,
            'age_seconds' => $secret->created_at->diffInSeconds(now()),
        ]);
    }
}
