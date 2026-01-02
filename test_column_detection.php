<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Smart Column Detection...\n\n";

    $mapper = new \App\Services\MappingService();

    // Test Case 1: File with EMPRESA column
    echo "Test 1: File with EMPRESA column\n";
    $headers1 = ['Data', 'Valor', 'EMPRESA', 'CNPJ'];
    $mapping1 = $mapper->getStandardMapping($headers1);
    echo "Detected company column: " . $mapping1['Razao Social'] . "\n";
    echo "Expected: EMPRESA\n";
    echo "Result: " . ($mapping1['Razao Social'] === 'EMPRESA' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    // Test Case 2: File with Histórico column (no EMPRESA)
    echo "Test 2: File with Histórico column (no EMPRESA)\n";
    $headers2 = ['Data', 'Valor', 'Histórico', 'CNPJ'];
    $mapping2 = $mapper->getStandardMapping($headers2);
    echo "Detected company column: " . $mapping2['Razao Social'] . "\n";
    echo "Expected: Histórico\n";
    echo "Result: " . ($mapping2['Razao Social'] === 'Histórico' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    // Test Case 3: File with both EMPRESA and Histórico (should prefer EMPRESA)
    echo "Test 3: File with both EMPRESA and Histórico (should prefer EMPRESA)\n";
    $headers3 = ['Data', 'Valor', 'EMPRESA', 'Histórico', 'CNPJ'];
    $mapping3 = $mapper->getStandardMapping($headers3);
    echo "Detected company column: " . $mapping3['Razao Social'] . "\n";
    echo "Expected: EMPRESA\n";
    echo "Result: " . ($mapping3['Razao Social'] === 'EMPRESA' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    // Test Case 4: File with lowercase empresa
    echo "Test 4: File with lowercase 'empresa' (case-insensitive)\n";
    $headers4 = ['Data', 'Valor', 'empresa', 'CNPJ'];
    $mapping4 = $mapper->getStandardMapping($headers4);
    echo "Detected company column: " . $mapping4['Razao Social'] . "\n";
    echo "Expected: empresa\n";
    echo "Result: " . ($mapping4['Razao Social'] === 'empresa' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    // Test Case 5: File with Historico (without accent)
    echo "Test 5: File with 'Historico' (without accent)\n";
    $headers5 = ['Data', 'Valor', 'Historico', 'CNPJ'];
    $mapping5 = $mapper->getStandardMapping($headers5);
    echo "Detected company column: " . $mapping5['Razao Social'] . "\n";
    echo "Expected: Historico\n";
    echo "Result: " . ($mapping5['Razao Social'] === 'Historico' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    // Test Case 6: File with Razao Social
    echo "Test 6: File with 'Razao Social'\n";
    $headers6 = ['Data', 'Valor', 'Razao Social', 'CNPJ'];
    $mapping6 = $mapper->getStandardMapping($headers6);
    echo "Detected company column: " . $mapping6['Razao Social'] . "\n";
    echo "Expected: Razao Social\n";
    echo "Result: " . ($mapping6['Razao Social'] === 'Razao Social' ? '✓ PASS' : '✗ FAIL') . "\n\n";

    echo "All tests completed!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
