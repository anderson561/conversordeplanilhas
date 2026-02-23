<?php

use App\Services\MappingService;
use App\Services\DominioTxtGeneratorService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);
$generator = app(DominioTxtGeneratorService::class);

echo "Testing Duplicate Removal and Sequence Priority...\n";

// Case: One REAL sale and one TRANSF that mentions the sale
$rows = [
    ['Data' => '22/12/2025', 'Valor' => '133333,33', 'Historico' => 'VENDA DOS APARTAMENTOS RESIDENCIAIS 1307'],
    ['Data' => '26/12/2025', 'Valor' => '133333,33', 'Historico' => 'TRANSF. ENTRE CONTAS REF. CRÉDITO VENDA DOS APARTAMENTOS']
];

$mappingRules = [
    'Data' => 'Data',
    'Valor' => 'Valor',
];

$rpsList = $mappingService->mapRowsToRps($rows, $mappingRules);

echo "Total RPS generated: " . count($rpsList) . " (Expected 1)\n";
foreach ($rpsList as $i => $rps) {
    echo "RPS $i: Name='{$rps->tomador->razaoSocial}', Date='{$rps->dataEmissao}'\n";
}

$options = ['starting_number' => 202602];
$txt = $generator->generateBatch($rpsList, 'test-job', [], $options);

echo "\nGenerated TXT Content:\n";
echo $txt;
