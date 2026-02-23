<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing Venda Priority in MappingService...\n";

// Case 1: Venda + Residencial (Should INCLUDE)
$row1 = [
    'Data' => '22/12/2025',
    'Valor' => '7425,66',
    'Razao Social' => 'VENDA DOS APARTAMENTOS RESIDENCIAIS 1307',
    'CNPJ' => '00.000.000/0000-00'
];

$mappingRules = [
    'Data' => 'Data',
    'Valor' => 'Valor',
    'Razao Social' => 'Razao Social',
    'CNPJ' => 'CNPJ'
];

$result1 = $mappingService->mapRowsToRps([$row1], $mappingRules);
echo "Case 1 (Venda + Residencial): " . (count($result1) === 1 ? "✅ Included" : "❌ Skipped") . "\n";

// Case 2: Venda + Transf (Should INCLUDE - per 'Sempre' instruction)
$row2 = $row1;
$row2['Razao Social'] = 'TRANSF REFERENTE A VENDA DE IMOVEL';
$result2 = $mappingService->mapRowsToRps([$row2], $mappingRules);
echo "Case 2 (Venda + Transf): " . (count($result2) === 1 ? "✅ Included" : "❌ Skipped") . "\n";

// Case 3: Just Transf (Should IGNORE)
$row3 = $row1;
$row3['Razao Social'] = 'TRANSF ENTRE CONTAS';
$result3 = $mappingService->mapRowsToRps([$row3], $mappingRules);
echo "Case 3 (Just Transf): " . (count($result3) === 0 ? "✅ Ignored" : "❌ Included") . "\n";

// Case 4: Just Residencial (Should IGNORE - if no venda)
$row4 = $row1;
$row4['Razao Social'] = 'RESIDENCIAIS SUMMARY';
$result4 = $mappingService->mapRowsToRps([$row4], $mappingRules);
echo "Case 4 (Just Residencial): " . (count($result4) === 0 ? "✅ Ignored" : "❌ Included") . "\n";
