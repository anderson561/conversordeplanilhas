<?php

namespace Tests\Feature;

use App\Models\User;
use App\Jobs\ProcessConversionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueueDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_job_is_dispatched_after_upload(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post('/uploads', [
            'file' => $file,
            'provider_cnpj' => '12.345.678/0001-90',
            'provider_razao_social' => 'Empresa Teste Ltda',
            'xml_type' => 'servico',
            'provider_uf' => 'BA',
            'provider_municipio' => 'Salvador'
        ]);

        $response->assertRedirect('/uploads');

        // Check if job was pushed to queue
        Queue::assertPushed(ProcessConversionJob::class);
    }
}
