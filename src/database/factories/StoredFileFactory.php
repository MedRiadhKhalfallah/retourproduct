<?php

namespace Database\Factories;

use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StoredFile>
 */
class StoredFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => StoredFile::PATH_PREFIX.'tests/'.Str::uuid(),
            'mime_type' => 'application/octet-stream',
            'content_base64' => base64_encode('stored file'),
        ];
    }
}
