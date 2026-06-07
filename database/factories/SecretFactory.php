<?php

namespace Database\Factories;

use App\Models\SharedFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Secret>
 */
class SecretFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraphs(3, true),
            'password' => null,
            'markdown_enabled' => false,
        ];
    }

    /**
     * Indicate that the secret has markdown enabled.
     */
    public function withMarkdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'markdown_enabled' => true,
        ]);
    }

    /**
     * Indicate that the secret is password protected.
     */
    public function withPassword(string $password = 'secret123'): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => $password,
        ]);
    }

    /**
     * Associate the secret with a shared file upload.
     */
    public function withFile(): static
    {
        return $this->state(fn () => [
            'shared_file_id' => SharedFile::factory(),
        ]);
    }
}
