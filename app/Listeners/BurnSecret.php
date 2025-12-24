<?php

namespace App\Listeners;

use App\Events\SecretRetrieved;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BurnSecret implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event.
     *
     * Deletes the secret after it has been retrieved.
     * Implements ShouldHandleEventsAfterCommit to ensure the deletion
     * only happens after the HTTP response is sent, preventing race conditions.
     */
    public function handle(SecretRetrieved $event): void
    {
        $event->secret->delete();
    }
}
