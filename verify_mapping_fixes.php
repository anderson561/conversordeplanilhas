<?php

use App\Services\MappingService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing MappingService Improvements...\n";

// Case 1: Duplicate with TRANSF
$excelRows = [
    [
        'Data' => '22/12/2025',
        'Valor' => '133.333,33',
        'Historico' => 'VENDA DOS APARTAMENTOS RESIDENCIAIS 1307'
    ],
    [
        'Data' => '26/12/2025',
        'Valor' => '133.333,33',
        'Historico' => 'TRANSF. ENTRE CONTAS REF. CRÉDITO VENDA DOS APARTAMENTOS'
    ]
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico'];
$mappedRows = $mappingService->mapRowsToRps($excelRows, $mappingRules);

echo "\nDuplicate Check:\n";
echo "Total Rows: " . count($mappedRows) . " (Expected 1)\n";
foreach ($mappedRows as $rps) {
    echo "- Name: {$rps->tomador->razaoSocial}, Date: {$rps->dataEmissao}\n";
}

// Case 2: CNPJ Extraction from Row Content
$excelRows2 = [
    [
        'Data' => '10/12/2025',
        'Valor' => '500,00',
        'Historico' => 'VENDA DE SERVICO CNPJ: 14.578.289/0003-05'
    ]
];

$mappedRows2 = $mappingService->mapRowsToRps($excelRows2, $mappingRules);
echo "\nCNPJ Extraction Check:\n";
foreach ($mappedRows2 as $rps) {
    echo "- Name: {$rps->tomador->razaoSocial}\n";
    echo "- CNPJ: {$rps->tomador->cpfCnpj} (Expected 14578289000305)\n";
}
