<?php

use App\Models\Upload;
use App\Services\FileParserService;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$uploadId = 33;
$upload = Upload::find($uploadId);

$fileParser = app(FileParserService::class);
$fullPath = Storage::path($upload->file_path);

echo "Data with Keys for Upload 33 (First 5 Data Rows):\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
    foreach ($rows->take(5) as $i => $row) {
        echo "\n--- Data Row $i ---\n";
        foreach ($row as $key => $val) {
            if ($val !== null && $val !== '') {
                echo "['$key'] => " . json_encode($val) . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
