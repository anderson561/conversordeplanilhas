<?php

use App\Services\MappingService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing ALUGUEL Inclusion...\n";

$rows = [
    // Case 1: Real Aluguel (Should INCLUDE)
    ['Data' => '02/12/2025', 'Valor' => '3376,67', 'Historico' => 'ALUGUEL ANUAL PDA 009B'],

    // Case 2: Venda (Should still INCLUDE)
    ['Data' => '22/12/2025', 'Valor' => '133333,33', 'Historico' => 'VENDA UNIDADE 101'],
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico'];
$mappedRows = $mappingService->mapRowsToRps($rows, $mappingRules);

echo "Total Rows captured: " . count($mappedRows) . " (Expected 2)\n";
foreach ($mappedRows as $rps) {
    echo "- Name: '{$rps->tomador->razaoSocial}'\n";
}
