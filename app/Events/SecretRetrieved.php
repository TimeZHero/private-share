<?php

namespace App\Events;

use App\Models\Secret;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SecretRetrieved
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Secret $secret,
        public ?string $ipAddress = null
    ) {
        Log::info('Secret retrieved', [
            'id' => $secret->id,
            'ip' => $ipAddress,
            'age_seconds' => $secret->created_at->diffInSeconds(now()),
        ]);
    }
}
