<?php

namespace App\Models;

use App\Observers\SharedFileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ObservedBy(SharedFileObserver::class)]
class SharedFile extends Model
{
    /** @use HasFactory<\Database\Factories\SharedFileFactory> */
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'original_name',
        'mime_type',
        'size',
        'storage_path',
        'encryption_salt',
        'client_iv',
        'client_encrypted',
    ];

    /** @var list<string> */
    protected $hidden = [
        'storage_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'client_encrypted' => 'boolean',
        ];
    }

    /**
     * @return string Human-readable file size.
     */
    public function formattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($index = 0; $bytes >= 1024 && $index < count($units) - 1; $index++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).' '.$units[$index];
    }

    protected static function booted(): void
    {
        static::creating(function (SharedFile $file) {
            if (empty($file->id)) {
                $file->id = self::generateUniqueId();
            }
        });
    }

    public static function generateUniqueId(): string
    {
        do {
            $id = Str::random(12);
        } while (self::whereId($id)->exists());

        return $id;
    }

    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->whereDate('created_at', '<', now()->subDays($days));
    }
}
