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

echo "Data with Keys for Upload 33 (Row 1 of data):\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
    if (count($rows) > 0) {
        $firstRow = $rows->first();
        foreach ($firstRow as $key => $val) {
            echo "['$key'] => " . json_encode($val) . "\n";
        }
    } else {
        echo "❌ No rows extracted.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
