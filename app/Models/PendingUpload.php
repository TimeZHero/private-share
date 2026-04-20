<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PendingUpload extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'id',
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
            'total_size' => 'integer',
            'total_chunks' => 'integer',
            'received_chunks' => 'integer',
        ];
    }

    public function isComplete(): bool
    {
        return $this->received_chunks >= $this->total_chunks;
    }

    public function scopeStale(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }
}
