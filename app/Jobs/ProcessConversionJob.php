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
        \App\Services\MappingService $mapper,
        \App\Services\ConversionService $converter
    ): void {
        // Increase memory limit for worker process
        ini_set('memory_limit', '1024M');
        set_time_limit(6); // 6 seconds

        try {
            \Log::info("Starting Conversion Job for Upload: {$this->upload->id}");
            $this->conversionJob->update(['started_at' => now(), 'status' => 'processing']);
            $this->upload->update(['status' => 'processing']);

            $fullPath = \Illuminate\Support\Facades\Storage::path($this->upload->file_path);
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found at path: {$fullPath}");
            }

            // Extract headers if missing
            $headers = $this->upload->meta_data['headers'] ?? null;
            if (!$headers) {
                \Log::info("Extracting headers for Upload: {$this->upload->id}");
                $parser = \App\Factories\ParserFactory::make($fullPath);
                $rows = $parser->parse($fullPath);
                $headers = !empty($rows) ? array_keys($rows[0]) : [];
                $this->upload->update([
                    'meta_data' => array_merge($this->upload->meta_data ?? [], ['headers' => $headers])
                ]);
            }

            // Process conversion
            \Log::info("Processing conversion logic for Upload: {$this->upload->id}");
            $converter->process($this->conversionJob, $mapper->getStandardMapping($headers));

            $this->upload->status = 'completed';
            $this->upload->save();

            $this->conversionJob->status = 'completed';
            $this->conversionJob->completed_at = now();
            $this->conversionJob->save();

            \Log::info("Conversion Job Completed Successfully for Upload: {$this->upload->id}. Final status: {$this->upload->status}");

            // Notify User
            try {
                \Illuminate\Support\Facades\Mail::to($this->upload->user->email)
                    ->send(new \App\Mail\ConversionCompletedMail($this->conversionJob));
            } catch (\Throwable $mailError) {
                \Log::warning("Could not send email for Upload: {$this->upload->id}: " . $mailError->getMessage());
            }

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
