<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing Noise Removal (Cents & Bank Fees)...\n";

$rows = [
    // Case 1: Real Venda (Should INCLUDE)
    ['Data' => '22/12/2025', 'Valor' => '133333,33', 'Historico' => 'VENDA UNIDADE 101'],

    // Case 2: Bank Fee (Should IGNORE)
    ['Data' => '01/12/2025', 'Valor' => '0,01', 'Historico' => 'TARIFA BANCARIA', 'CNPJ' => '12.453.169/0001-86'],

    // Case 3: Tax (Should IGNORE)
    ['Data' => '09/12/2025', 'Valor' => '0,62', 'Historico' => 'IOF S/ OPERACAO', 'CNPJ' => '12.453.169/0001-86'],

    // Case 4: Random Noise > 2.00 but with stop-word (Should IGNORE)
    ['Data' => '10/12/2025', 'Valor' => '15,00', 'Historico' => 'PAGTO TITULO ITAU'],

    // Case 5: Empty Name with CNPJ (Should IGNORE)
    ['Data' => '15/12/2025', 'Valor' => '0,04', 'Historico' => '', 'CNPJ' => '12.453.169/0001-86']
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico', 'CNPJ' => 'CNPJ'];
$mappedRows = $mappingService->mapRowsToRps($rows, $mappingRules);

echo "Total Rows captured: " . count($mappedRows) . " (Expected 1)\n";
foreach ($mappedRows as $rps) {
    echo "- Name: '{$rps->tomador->razaoSocial}', Value: '{$rps->servico->valorServico}'\n";
}
