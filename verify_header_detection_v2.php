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

echo "Testing header detection for Upload 33...\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);

    echo "Resulting Row Count: " . count($rows) . "\n";
    if (count($rows) > 0) {
        $firstRow = $rows->first();
        echo "First Row Keys: " . implode(', ', array_keys($firstRow)) . "\n";
        echo "First Row Values Snippet: " . json_encode(array_slice($firstRow, 0, 5)) . "\n";
    } else {
        echo "❌ No rows extracted.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
