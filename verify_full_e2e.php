<?php

use App\Models\Upload;
use App\Services\FileParserService;
use App\Services\MappingService;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$uploadId = 33;
$upload = Upload::find($uploadId);

$fileParser = app(FileParserService::class);
$mappingService = app(MappingService::class);
$fullPath = Storage::path($upload->file_path);

$mappingRules = [
    'Data' => 'DATA',
    'Valor' => 'VALOR TOTAL',
    'Razao Social' => 'HISTÓRICO',
    'CNPJ' => 'CPF/CNPJ'
];

echo "Full E2E Test for Upload 33...\n";
try {
    $rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
    echo "Rows from parser: " . count($rows) . "\n";

    $rpsList = $mappingService->mapRowsToRps($rows->toArray(), $mappingRules);
    echo "Rows after mapping/filtering: " . count($rpsList) . "\n";

    if (count($rpsList) > 0) {
        echo "✅ SUCCESS: " . count($rpsList) . " rows mapped.\n";
        echo "Sample Mapping (Row 1):\n";
        $rps = $rpsList[0];
        echo " - Tomador: " . $rps->tomador->razaoSocial . "\n";
        echo " - Data: " . $rps->dataEmissao . "\n";
        echo " - Valor: " . $rps->servico->valorServico . "\n";
    } else {
        echo "❌ FAILURE: All rows filtered out.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
