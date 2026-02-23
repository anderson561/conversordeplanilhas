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

echo "Header Chars for Upload 33:\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
    if (count($rows) > 0) {
        $firstRow = $rows->first();
        foreach (array_keys($firstRow) as $key) {
            echo "Key: '$key' (Length: " . strlen($key) . ") Chars: ";
            for ($i = 0; $i < strlen($key); $i++) {
                echo ord($key[$i]) . " ";
            }
            echo "\n";
        }
    } else {
        echo "❌ No rows extracted.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
