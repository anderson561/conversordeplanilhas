<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing Final Noise Suppression...\n";

$rows = [
    // Case 1: Name is just a space (The likely culprit)
    ['Data' => '01/12/2025', 'Valor' => '0,01', 'Historico' => ' ', 'CNPJ' => '12.453.169/0001-86'],

    // Case 2: Receita Federal CNPJ (Tax)
    ['Data' => '09/12/2025', 'Valor' => '0,62', 'Historico' => 'IOF S/ PAGTO', 'CNPJ' => '12.453.169/0001-86'],

    // Case 3: Real Venda with bank garbage (Should INCLUDE and CLEAN)
    ['Data' => '22/12/2025', 'Valor' => '133333,33', 'Historico' => '3 46013 VENDA UNIDADE 101'],
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico', 'CNPJ' => 'CNPJ'];
$mappedRows = $mappingService->mapRowsToRps($rows, $mappingRules);

echo "Total Rows captured: " . count($mappedRows) . " (Expected 1)\n";
foreach ($mappedRows as $rps) {
    echo "- Name: '{$rps->tomador->razaoSocial}', Value: '{$rps->servico->valorServico}'\n";
    if (str_contains($rps->tomador->razaoSocial, '3 46013')) {
        echo "❌ Garbage '3 46013' NOT removed!\n";
    } else {
        echo "✅ Garbage removed.\n";
    }
}
