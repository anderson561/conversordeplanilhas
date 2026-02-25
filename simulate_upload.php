<?php

use App\Models\Upload;
use App\Models\ConversionJob;
use App\Models\User;
use App\Jobs\ProcessConversionJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Simulating E2E Upload and Conversion...\n";

// 1. Get a user
$user = User::first();
if (!$user) {
    echo "No user found in DB!\n";
    exit(1);
}

// 2. Create a dummy file in storage with valid-ish header
$filePath = 'uploads/test_sim_' . time() . '.pdf';
Storage::disk('local')->put($filePath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

echo "Created dummy PDF at: $filePath\n";

// 3. Create Upload Record
$upload = Upload::create([
    'user_id' => $user->id,
    'file_path' => $filePath,
    'original_name' => 'sim_test.pdf',
    'mime_type' => 'application/pdf',
    'xml_type' => 'servico',
    'status' => 'pending',
    'provider_info' => [
        'cnpj' => '12.345.678/0001-90',
        'razao_social' => 'Simulated Test Ltda',
    ],
    'meta_data' => [
        'excel_output_type' => 'saida',
    ],
]);

echo "Created Upload ID: {$upload->id}\n";

// 4. Create Conversion Job Record
$conversionJob = ConversionJob::create([
    'upload_id' => $upload->id,
    'status' => 'pending',
]);

echo "Created Conversion Job ID: {$conversionJob->id}\n";

// 5. Dispatch Job
ProcessConversionJob::dispatch($upload, $conversionJob);

echo "Job dispatched. Waiting for worker...\n";

// 7. Wait and check status
for ($i = 0; $i < 15; $i++) {
    sleep(1);
    $u = Upload::find($upload->id);
    $j = ConversionJob::find($conversionJob->id);
    echo "T+{$i}s - Upload: {$u->status}, Job: {$j->status}\n";
    if ($u->status === 'completed' || $u->status === 'failed')
        break;
}

if ($u->status === 'completed') {
    echo "✅ Success! Worker is processing jobs.\n";
} else if ($u->status === 'failed') {
    echo "❌ Failed! Error: " . $j->error_message . "\n";
} else {
    echo "⏳ Still Pending. Worker might not be running or is stuck.\n";
}
