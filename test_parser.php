<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\FileParserService;

$parser = new App\Services\FileParserService();

try {
    $headers = $parser->getHeaders(__DIR__ . '/test_rps.xlsx');
    echo "Headers encontrados:\n";
    print_r($headers);
    echo "\n\nTotal: " . count($headers) . " colunas\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
