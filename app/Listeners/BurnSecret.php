<?php

namespace App\Listeners;

use App\Events\SecretRetrieved;

class BurnSecret
{
    /**
     * Handle the event.
     */
    public function handle(SecretRetrieved $event): void
    {
        $event->secret->delete();
    }
}
