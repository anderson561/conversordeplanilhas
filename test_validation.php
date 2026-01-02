<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Data Validation Service...\n\n";

    $validator = new \App\Services\DataValidationService();
    $mappingRules = [
        'Data' => 'Data',
        'Valor' => 'Valor',
        'Razao Social' => 'Razao Social',
        'CNPJ' => 'CNPJ',
    ];

    // Test Case 1: Valid Row
    echo "Test 1: Valid Row\n";
    $validRow = [
        'Data' => '18/11/2025',
        'Valor' => '3.000,00',
        'Razao Social' => 'EMPRESA TESTE',
        'CNPJ' => '12.345.678/0001-90'
    ];
    $errors1 = $validator->validateRow($validRow, $mappingRules, 1);
    if (empty($errors1)) {
        echo "✓ PASS: Valid row has no errors\n";
    } else {
        echo "✗ FAIL: Valid row has errors: " . implode(', ', $errors1) . "\n";
    }
    echo "\n";

    // Test Case 2: Invalid Date
    echo "Test 2: Invalid Date\n";
    $invalidDateRow = $validRow;
    $invalidDateRow['Data'] = '2025-11-18'; // Wrong format
    $errors2 = $validator->validateRow($invalidDateRow, $mappingRules, 2);
    if (count($errors2) === 1 && strpos($errors2[0], 'Data') !== false) {
        echo "✓ PASS: Detected invalid date format\n";
    } else {
        echo "✗ FAIL: Expected date error, got: " . implode(', ', $errors2) . "\n";
    }
    echo "\n";

    // Test Case 3: Invalid Valor
    echo "Test 3: Invalid Valor\n";
    $invalidValorRow = $validRow;
    $invalidValorRow['Valor'] = '-100,00'; // Negative
    $errors3 = $validator->validateRow($invalidValorRow, $mappingRules, 3);
    if (count($errors3) === 1 && strpos($errors3[0], 'Valor') !== false) {
        echo "✓ PASS: Detected invalid valor\n";
    } else {
        echo "✗ FAIL: Expected valor error, got: " . implode(', ', $errors3) . "\n";
    }
    echo "\n";

    // Test Case 4: Invalid Razao Social
    echo "Test 4: Invalid Razao Social\n";
    $invalidNameRow = $validRow;
    $invalidNameRow['Razao Social'] = 'AB'; // Too short
    $errors4 = $validator->validateRow($invalidNameRow, $mappingRules, 4);
    if (count($errors4) === 1 && strpos($errors4[0], 'Razão Social') !== false) {
        echo "✓ PASS: Detected short name\n";
    } else {
        echo "✗ FAIL: Expected name error, got: " . implode(', ', $errors4) . "\n";
    }
    echo "\n";

    // Test Case 5: Invalid CNPJ
    echo "Test 5: Invalid CNPJ\n";
    $invalidCnpjRow = $validRow;
    $invalidCnpjRow['CNPJ'] = '123'; // Too short
    $errors5 = $validator->validateRow($invalidCnpjRow, $mappingRules, 5);
    if (count($errors5) === 1 && strpos($errors5[0], 'CNPJ') !== false) {
        echo "✓ PASS: Detected invalid CNPJ\n";
    } else {
        echo "✗ FAIL: Expected CNPJ error, got: " . implode(', ', $errors5) . "\n";
    }
    echo "\n";

    echo "All tests completed!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
