<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Upload;
use Illuminate\Support\Facades\Storage;

$upload = Upload::find(3);

if ($upload) {
    echo "Upload encontrado:\n";
    echo "ID: " . $upload->id . "\n";
    echo "Nome: " . $upload->original_name . "\n";
    echo "Path: " . $upload->file_path . "\n";
    echo "User ID: " . $upload->user_id . "\n";
    echo "Meta data: " . json_encode($upload->meta_data) . "\n";
    echo "File exists: " . (Storage::exists($upload->file_path) ? 'SIM' : 'NAO') . "\n";

    if (Storage::exists($upload->file_path)) {
        $fullPath = Storage::path($upload->file_path);
        echo "Full path: " . $fullPath . "\n";
        echo "File size: " . filesize($fullPath) . " bytes\n";
    }
} else {
    echo "Upload ID 3 não encontrado\n";
}
