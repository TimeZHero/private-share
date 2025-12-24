<?php

namespace App\Models;

use App\Observers\SecretObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ObservedBy(SecretObserver::class)]
class Secret extends Model
{
    /** @use HasFactory<\Database\Factories\SecretFactory> */
    use HasFactory;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content',
        'requires_confirmation',
        'password',
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
            'requires_confirmation' => 'boolean',
            'password' => 'hashed',
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
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Secret $secret) {
            if (empty($secret->id)) {
                $secret->id = self::generateUniqueId();
            }
        });
    }

    /**
     * Generate a unique 12-character ID.
     */
    public static function generateUniqueId(): string
    {
        do {
            $id = Str::random(12);
        } while (self::whereId($id)->exists());

        return $id;
    }

    /**
     * Scope a query to only include secrets older than the given number of days.
     */
    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->whereDate('created_at', '<', now()->subDays($days));
    }
}
