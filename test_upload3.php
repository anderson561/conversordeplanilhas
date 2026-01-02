<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Upload;
use App\Services\FileParserService;
use Illuminate\Support\Facades\Storage;

$upload = Upload::find(3);

if (!$upload) {
    echo "Upload não encontrado\n";
    exit(1);
}

echo "Testando extração de headers...\n";
echo "Arquivo: " . $upload->file_path . "\n\n";

$parser = new FileParserService();

try {
    $fullPath = Storage::path($upload->file_path);
    echo "Full path: $fullPath\n";
    echo "File exists: " . (file_exists($fullPath) ? 'SIM' : 'NAO') . "\n\n";

    $headers = $parser->getHeaders($fullPath);

    echo "SUCCESS! Headers extraídos:\n";
    print_r($headers);
    echo "\nTotal: " . count($headers) . " colunas\n";

    // Tentar atualizar o upload
    $upload->update([
        'meta_data' => ['headers' => $headers]
    ]);

    echo "\nUpload atualizado com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
