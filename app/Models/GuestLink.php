<?php

namespace App\Models;

use Database\Factories\GuestLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GuestLink extends Model
{
    /** @use HasFactory<GuestLinkFactory> */
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GuestLink $guestLink) {
            if (empty($guestLink->id)) {
                $guestLink->id = Str::random(32);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Marks the link as used unless it already was or has expired. A single
     * conditional UPDATE, so two concurrent clicks cannot both get in.
     */
    public function consume(): bool
    {
        $consumed = static::query()
            ->whereKey($this->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]) === 1;

        if ($consumed) {
            $this->refresh();
        }

        return $consumed;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
