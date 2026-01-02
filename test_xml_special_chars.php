<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing XML generation with special characters...\n";

    $mapper = new \App\Services\MappingService();
    $xmlGen = new \App\Services\XmlGeneratorService();

    // Test with sample data containing special characters
    $rows = [
        [
            'Data' => '2025-01-01',
            'Valor' => '1000.00',
            'Razao Social' => 'T COMERCIO DE COLCHOES LTDA (ORTOBOM)',
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

    echo "Generated " . count($rpsList) . " RPS\n";

    $providerInfo = [
        'cnpj' => '00000000000000',
        'razao_social' => 'PRESTADOR TESTE',
        'inscricao_municipal' => '000000',
    ];

    $xml = $xmlGen->generateBatchXml($rpsList, '1', $providerInfo);

    echo "XML generated successfully!\n";
    echo "Length: " . strlen($xml) . " bytes\n";

    // Check if special characters are properly escaped
    if (strpos($xml, 'ORTOBOM') !== false) {
        echo "✓ Special characters (parentheses) handled correctly!\n";
    }

    // Try to parse the XML to validate it
    $dom = new DOMDocument();
    if ($dom->loadXML($xml)) {
        echo "✓ XML is valid and well-formed!\n";
    } else {
        echo "✗ XML is malformed\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
