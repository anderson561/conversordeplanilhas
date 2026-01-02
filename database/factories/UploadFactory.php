<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Upload>
 */
class UploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'file_path' => 'uploads/test.pdf',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'file_hash' => \Illuminate\Support\Str::random(64),
            'provider_info' => ['cnpj' => '12.345.678/0001-90', 'razao_social' => 'Empresa Teste'],
        ];
    }
}
