<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class PendingUpload extends Model
{
    /** Must match FILE_CHUNK_SIZE in resources/js/lib/crypto.ts. */
    public const int CHUNK_BYTES = 8 * 1024 * 1024;

    /** AES-GCM authentication tag appended to every encrypted chunk. */
    public const int TAG_BYTES = 16;

    /**
     * Uploads cannot be resumed, so one that received no chunk for this long is dead.
     * Judged by the last chunk, not the start, so slow but active uploads survive.
     */
    public const int IDLE_MINUTES = 60;

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

    public function scopeIdle(Builder $query): Builder
    {
        return $query->where('updated_at', '<', now()->subMinutes(self::IDLE_MINUTES));
    }

    public static function temporaryDirectory(): string
    {
        return storage_path('app/private/uploads');
    }

    /**
     * Creates the chunk directory readable by the app user only, tightening
     * directories created before this was enforced.
     */
    public static function prepareTemporaryDirectory(): string
    {
        $directory = self::temporaryDirectory();

        File::ensureDirectoryExists($directory, 0700);

        if ((fileperms($directory) & 0777) !== 0700) {
            chmod($directory, 0700);
        }

        return $directory;
    }
}
