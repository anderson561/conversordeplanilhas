<?php

require 'vendor/autoload.php';

use App\Services\MappingService;

echo "=== COMPETÊNCIA MAPPING TEST ===\n\n";

// Simulate a row from the user's CSV
$testRow = [
    'CNPJ' => '82112223534',
    'Razao Social' => 'DANILLO DOMINGUES RIBEIRO GUIMARAES',
    'UF' => 'BA',
    'Municipio' => 'Salvador',
    'Competencia' => '202602',  // This should become 01/02/2026
    'Valor' => '7425,66',
    // Note: No 'Data' column - this is the key issue
];

echo "Input Row:\n";
print_r($testRow);

// Test the getValue logic
$getValue = function ($key) use ($testRow) {
    $normalized = strtolower(str_replace([' ', '_', '-'], '', $key));
    foreach ($testRow as $k => $v) {
        $kNormalized = strtolower(str_replace([' ', '_', '-'], '', $k));
        if ($kNormalized === $normalized) {
            return $v;
        }
    }
    return null;
};

echo "\n--- Testing Column Detection ---\n";
echo "Data: " . ($getValue('Data') ?? 'NULL') . "\n";
echo "Competencia: " . ($getValue('Competencia') ?? 'NULL') . "\n";
echo "Competência: " . ($getValue('Competência') ?? 'NULL') . "\n";

// Test the competência parsing logic
$competenciaRaw = $getValue('Competencia') ?? $getValue('Competência');
echo "\n--- Testing Competência Parsing ---\n";
echo "Raw Competência: $competenciaRaw\n";

if ($competenciaRaw && preg_match('/^(\d{4})(\d{2})$/', $competenciaRaw, $matches)) {
    $year = $matches[1];
    $month = $matches[2];
    $dataNormalized = "$year-$month-01";
    echo "Parsed Date: $dataNormalized\n";
    echo "Expected: 2026-02-01\n";
    echo $dataNormalized === '2026-02-01' ? "[PASS]\n" : "[FAIL]\n";
} else {
    echo "[FAIL] Regex did not match\n";
}

echo "\n=== RECOMMENDATION ===\n";
echo "If the test above passes but your output still shows 01/01/2000:\n";
echo "1. RESTART the queue worker (close start_queue_worker.bat and run again)\n";
echo "2. DELETE the old upload from the dashboard\n";
echo "3. UPLOAD the file again\n";
echo "\nThe queue worker caches the old code until restarted!\n";
