<?php

namespace Database\Factories;

use App\Models\SharedFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SharedFile>
 */
class SharedFileFactory extends Factory
{
    protected $model = SharedFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'storage_path' => 'shared-files/'.fake()->uuid().'.enc',
            'encryption_salt' => base64_encode(random_bytes(16)),
            'client_iv' => base64_encode(random_bytes(12)),
            'client_encrypted' => true,
        ];
    }
}
