<?php

use App\Services\MappingService;
use App\DTOs\RpsData;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mappingService = app(MappingService::class);

echo "Testing Exclusion of Cent-level Noise with Receita CNPJ...\n";

// This simulates the problematic row reported by the user
$rowNoise = [
    'Data' => '01/12/2025',
    'Valor' => '0,01',
    'CNPJ' => '12.453.169/0001-86', // Receita Federal
    'Descricao' => 'IOF S/ PAGTO',  // This has stop-words
];

$mappingRules = [
    'Data' => 'Data',
    'Valor' => 'Valor',
    'CNPJ' => 'CNPJ',
    'Razao Social' => 'NomeInexistente' // Simulating empty name mapping
];

$results = $mappingService->mapRowsToRps([$rowNoise], $mappingRules);
$result = $results[0] ?? null;

if ($result === null) {
    echo "✅ Row correctly skipped by mapSingleRow.\n";
} else {
    echo "❌ Row INCORRECTLY mapped.\n";
    echo "   Name: '{$result->tomador->razaoSocial}'\n";
    echo "   Value: '{$result->servico->valorServico}'\n";

    // Check validation
    $refl = new ReflectionClass($mappingService);
    $method = $refl->getMethod('isValidRps');
    $method->setAccessible(true);
    $isValid = $method->invoke($mappingService, $result);

    echo "   IsValid: " . ($isValid ? "TRUE ❌" : "FALSE ✅") . "\n";
}
