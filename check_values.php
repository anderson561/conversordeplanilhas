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

echo "Checking Row 1 Values for Upload 33:\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
    if (count($rows) > 0) {
        $firstRow = $rows->first();
        $keysToTest = ['DATA', 'HISTÓRICO', 'CPF/CNPJ', 'VALOR TOTAL'];
        foreach ($keysToTest as $key) {
            $val = $firstRow[$key] ?? 'MISSING';
            echo "Key '$key' => " . json_encode($val) . " (Type: " . gettype($val) . ")\n";
        }
    } else {
        echo "❌ No rows extracted.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
