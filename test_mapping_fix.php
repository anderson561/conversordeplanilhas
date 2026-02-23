<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mapper = app(MappingService::class);

$row = [
    'Column_0' => '1',
    'Column_1' => '46031',
    'Column_2' => 'Some Client',
    'Column_3' => '12.345.678/0001-90',
    'Column_4' => 'VENDA DE PRODUTOS',
    'Column_5' => '100.00',
    'Column_6' => 'TAXA DE SERVIÇO' // This would trigger isForbidden
];

$mappingRules = [
    'Data' => 'Column_1',
    'Valor' => 'Column_5',
    'Razao Social' => 'Column_2',
    'CNPJ' => 'Column_3'
];

echo "Testing row with VENDA and TAXA...\n";
$rps = $mapper->mapSingleRow($row, $mappingRules);
if ($rps) {
    echo "✅ Row Included\n";
} else {
    echo "❌ Row Forbidden\n";
}
