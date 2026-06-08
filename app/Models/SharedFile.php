<?php

namespace App\Models;

use App\Models\Concerns\HasShortId;
use App\Observers\SharedFileObserver;
use Database\Factories\SharedFileFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(SharedFileObserver::class)]
class SharedFile extends Model
{
    /** @use HasFactory<SharedFileFactory> */
    use HasFactory;

    use HasShortId;

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

    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->whereDate('created_at', '<', now()->subDays($days));
    }
}
