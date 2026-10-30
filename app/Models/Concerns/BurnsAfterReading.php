<?php

namespace App\Models\Concerns;

/**
 * Single-use records: the database decides which of several concurrent
 * readers wins, so content is never handed out twice.
 */
trait BurnsAfterReading
{
    /**
     * Deletes the row without firing model events. Only the caller that gets
     * true may serve the content.
     */
    public function claim(): bool
    {
        $claimed = static::query()->whereKey($this->getKey())->delete() === 1;

        if ($claimed) {
            $this->exists = false;
        }

        return $claimed;
    }

    /**
     * Claims the record and runs the usual deletion side effects (observers, logging).
     */
    public function burn(): bool
    {
        if (! $this->claim()) {
            return false;
        }

        $this->fireModelEvent('deleted', false);

        return true;
    }
}
