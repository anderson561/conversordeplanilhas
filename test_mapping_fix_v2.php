<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mapper = app(MappingService::class);

$rows = [
    [
        'ITEM' => '1',
        'DATA' => '46031',
        'CLIENTE' => 'Some Client',
        'CNPJ' => '12.345.678/0001-90',
        'HISTORICO' => 'VENDA DE PRODUTOS',
        'VALOR' => '100.00',
        'EXTRA' => 'TAXA DE SERVIÇO' // This would trigger isForbidden
    ]
];

$mappingRules = [
    'Data' => 'DATA',
    'Valor' => 'VALOR',
    'Razao Social' => 'CLIENTE',
    'CNPJ' => 'CNPJ'
];

echo "Testing mapRowsToRps with VENDA and TAXA...\n";
try {
    $rpsList = $mapper->mapRowsToRps($rows, $mappingRules);
    if (!empty($rpsList)) {
        echo "✅ Row Included in list\n";
        echo "Data: " . $rpsList[0]->tomador->razaoSocial . "\n";
    } else {
        echo "❌ Row Filtered Out\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
