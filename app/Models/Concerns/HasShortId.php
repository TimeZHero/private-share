<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Provides a random 12-character alphanumeric primary key
 * with collision checking, replacing auto-increment integers.
 */
trait HasShortId
{
    public function initializeHasShortId(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootHasShortId(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = static::generateUniqueId();
            }
        });
    }

    public static function generateUniqueId(): string
    {
        do {
            $id = Str::random(12);
        } while (static::whereId($id)->exists());

        return $id;
    }
}
