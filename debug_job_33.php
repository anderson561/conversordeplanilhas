<?php

use App\Models\Upload;
use App\Models\ConversionJob;
use App\Services\FileParserService;
use App\Services\MappingService;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$uploadId = 33;
$upload = Upload::find($uploadId);

if (!$upload) {
    die("Upload $uploadId not found\n");
}

$fileParser = app(FileParserService::class);
$mapper = app(MappingService::class);

$fullPath = Storage::path($upload->file_path);
echo "File: $fullPath\n";

$rows = $fileParser->getRows($fullPath, null, $upload->mime_type);
echo "Total rows parsed: " . count($rows) . "\n";

$mappingRules = [
    'Data' => 'DATA',
    'Valor' => 'VALOR',
    'Razao Social' => 'HISTORICO',
    'CNPJ' => 'CNPJ'
];

foreach ($rows as $index => $row) {
    echo "\n--- Row " . ($index + 1) . " ---\n";
    echo "Content: " . json_encode($row) . "\n";

    $rowString = mb_strtoupper(implode(' ', array_filter(array_values($row))));
    echo "RowString: $rowString\n";

    $isForbidden = str_contains($rowString, 'TRANSF') ||
        str_contains($rowString, 'TRANSFERÊNCIA') ||
        str_contains($rowString, 'TRANSFERENCIA') ||
        str_contains($rowString, 'CRÉDITO') ||
        str_contains($rowString, 'CREDITO') ||
        str_contains($rowString, 'RESGATE') ||
        str_contains($rowString, 'RENDIMENTO') ||
        str_contains($rowString, 'APLICAÇÃO') ||
        str_contains($rowString, 'IOF') ||
        str_contains($rowString, 'IRRF') ||
        str_contains($rowString, 'TARIFA') ||
        str_contains($rowString, 'TAXA') ||
        str_contains($rowString, 'IMPOSTO') ||
        str_contains($rowString, 'JUROS') ||
        str_contains($rowString, 'DEBITO') ||
        str_contains($rowString, 'PAGTO') ||
        str_contains($rowString, 'PAGAMENTO') ||
        str_contains($rowString, 'RENTAB') ||
        str_contains($rowString, 'DIVIDENDO');

    if ($isForbidden) {
        echo "❌ FORBIDDEN\n";
    } else {
        $rps = $mapper->mapRowsToRps([$row], $mappingRules);
        if (empty($rps)) {
            echo "❌ FILTERED OUT (isValidRps)\n";
        } else {
            echo "✅ INCLUDED\n";
        }
    }
}
