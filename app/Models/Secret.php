<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
    ];

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
}
