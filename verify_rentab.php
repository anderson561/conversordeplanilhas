<?php

use App\Services\MappingService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing RENTAB Exclusion...\n";

$rows = [
    // Should be IGNORED
    ['Data' => '01/12/2025', 'Valor' => '100,00', 'Historico' => 'RENTAB INVEST FÁCIL CRED'],
    ['Data' => '04/12/2025', 'Valor' => '200,00', 'Historico' => 'RESGATE CDB'],
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico'];
$mappedRows = $mappingService->mapRowsToRps($rows, $mappingRules);

echo "Total Rows captured: " . count($mappedRows) . " (Expected 0)\n";
foreach ($mappedRows as $rps) {
    echo "- Name: '{$rps->tomador->razaoSocial}'\n";
}
