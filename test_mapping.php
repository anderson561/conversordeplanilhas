<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing MappingService...\n";

    $mapper = new \App\Services\MappingService();

    // Test with sample data
    $rows = [
        [
            'Data' => '2025-01-01',
            'Valor' => '1000.00',
            'Razao Social' => 'Empresa Teste LTDA',
            'CNPJ' => '12.345.678/0001-90'
        ]
    ];

    $mappingRules = [
        'Data' => 'Data',
        'Valor' => 'Valor',
        'Razao Social' => 'Razao Social',
        'CNPJ' => 'CNPJ',
    ];

    $rpsList = $mapper->mapRowsToRps($rows, $mappingRules);

    echo "Success! Generated " . count($rpsList) . " RPS\n";
    print_r($rpsList[0]);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
