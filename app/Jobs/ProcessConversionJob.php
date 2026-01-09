<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessConversionJob implements ShouldQueue
{
    use Queueable;

    protected $upload;
    protected $conversionJob;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Upload $upload, \App\Models\ConversionJob $conversionJob)
    {
        $this->upload = $upload;
        $this->conversionJob = $conversionJob;
    }

    /**
     * Execute the job.
     */
    public function handle(
        \App\Services\FileParserService $fileParser,
        \App\Services\MappingService $mapper,
        \App\Services\ConversionService $converter
    ): void {
        // Increase memory limit for worker process
        ini_set('memory_limit', '1024M');
        set_time_limit(600); // 10 minutes

        try {
            \Log::info("Starting Conversion Job for Upload: {$this->upload->id}");
            $this->conversionJob->update(['started_at' => now(), 'status' => 'processing']);
            $this->upload->update(['status' => 'processing']);

            $fullPath = \Illuminate\Support\Facades\Storage::path($this->upload->file_path);

            // Extract headers if missing
            $headers = $this->upload->meta_data['headers'] ?? null;
            if (!$headers) {
                $headers = $fileParser->getHeaders($fullPath, $this->upload->mime_type);
                $this->upload->update([
                    'meta_data' => array_merge($this->upload->meta_data ?? [], ['headers' => $headers])
                ]);
            }

            // Process conversion
            $converter->process($this->conversionJob, $mapper->getStandardMapping($headers));

            $this->upload->update(['status' => 'completed']);
            $this->conversionJob->update(['status' => 'completed', 'completed_at' => now()]);
            \Log::info("Conversion Job Completed Successfully for Upload: {$this->upload->id}");

            // Notify User
            \Illuminate\Support\Facades\Mail::to($this->upload->user->email)
                ->send(new \App\Mail\ConversionCompletedMail($this->conversionJob));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Background Conversion Failed for Upload ' . $this->upload->id . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $this->upload->update(['status' => 'failed']);
            $this->conversionJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);

            throw $e; // Fail the job so it can be retried if configured
        }
    }
}
