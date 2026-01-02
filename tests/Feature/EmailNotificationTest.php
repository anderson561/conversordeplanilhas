<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Upload;
use App\Models\ConversionJob;
use App\Mail\ConversionCompletedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_email_when_conversion_is_completed(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);
        $upload = Upload::factory()->create(['user_id' => $user->id]);
        $conversionJob = ConversionJob::factory()->create([
            'upload_id' => $upload->id,
            'status' => 'pending'
        ]);

        // Simulating the Job Execution manually or via Service
        $job = new \App\Jobs\ProcessConversionJob($upload, $conversionJob);

        // Mocking dependencies to avoid real file parsing in this test
        $fileParser = \Mockery::mock(\App\Services\FileParserService::class);
        $mapper = \Mockery::mock(\App\Services\MappingService::class);
        $converter = \Mockery::mock(\App\Services\ConversionService::class);

        $fileParser->shouldReceive('getHeaders')->andReturn(['Data', 'Valor']);
        $mapper->shouldReceive('getStandardMapping')->andReturn(['Data' => 'Data', 'Valor' => 'Valor']);
        $converter->shouldReceive('process')->once();

        // Run the job
        $job->handle($fileParser, $mapper, $converter);

        // Assert
        Mail::assertSent(ConversionCompletedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
