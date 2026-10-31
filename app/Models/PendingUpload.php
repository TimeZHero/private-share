<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PendingUpload extends Model
{
    /** Must match FILE_CHUNK_SIZE in resources/js/lib/crypto.ts. */
    public const int CHUNK_BYTES = 8 * 1024 * 1024;

    /** AES-GCM authentication tag appended to every encrypted chunk. */
    public const int TAG_BYTES = 16;

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'user_id',
        'guest_link_id',
        'original_name',
        'mime_type',
        'total_size',
        'total_chunks',
        'received_chunks',
        'temp_path',
        'encryption_salt',
        'client_iv',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'total_size' => 'integer',
            'total_chunks' => 'integer',
            'received_chunks' => 'integer',
        ];
    }

    public function isComplete(): bool
    {
        return $this->received_chunks >= $this->total_chunks;
    }

    public static function chunksFor(int $size): int
    {
        return (int) ceil($size / self::CHUNK_BYTES);
    }

    public function expectedStoredBytes(): int
    {
        return $this->total_size + $this->total_chunks * self::TAG_BYTES;
    }

    public function receivedBytes(): int
    {
        clearstatcache(true, $this->temp_path);

        return file_exists($this->temp_path) ? (int) filesize($this->temp_path) : 0;
    }

    public function scopeStale(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }
}
