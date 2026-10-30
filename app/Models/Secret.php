<?php

namespace App\Models;

use App\Models\Concerns\HasShortId;
use App\Observers\SecretObserver;
use Database\Factories\SecretFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(SecretObserver::class)]
class Secret extends Model
{
    use BurnsAfterReading;

    /** @use HasFactory<SecretFactory> */
    use HasFactory;

    use HasShortId;

    /**
     * Unread secrets expire after this many days: refused at read time, deleted by secrets:cleanup.
     */
    public const int LIFETIME_DAYS = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content',
        'password',
        'markdown_enabled',
        'shared_file_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'markdown_enabled' => 'boolean',
        ];
    }

    /**
     * Check if the secret is password protected.
     */
    public function isPasswordProtected(): bool
    {
        return $this->password !== null;
    }

    /**
     * @return BelongsTo<SharedFile, $this>
     */
    public function sharedFile(): BelongsTo
    {
        return $this->belongsTo(SharedFile::class);
    }

    public function hasFile(): bool
    {
        return $this->shared_file_id !== null;
    }

    /**
     * Scope a query to only include secrets older than the given number of days.
     */
    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->whereDate('created_at', '<', now()->subDays($days));
    }

    /**
     * Route model binding never resolves expired secrets, even before cleanup removes them.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->where('created_at', '>', now()->subDays(self::LIFETIME_DAYS));
    }
}
