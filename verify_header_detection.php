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
$rows = $fileParser->getRows($fullPath, null, $upload->mime_type);

echo "Resulting Row Count: " . count($rows) . "\n";
if (count($rows) > 0) {
    echo "First Row Keys: " . implode(', ', array_keys($rows[0]->toArray())) . "\n";
    echo "First Row Values: " . json_encode($rows[0]) . "\n";
} else {
    echo "❌ No rows extracted.\n";
}
