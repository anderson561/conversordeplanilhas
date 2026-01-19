<?php

echo "=== CRÉDITO FILTER VERIFICATION ===\n\n";

// Test cases
$testLines = [
    "BANCO BRASIL 04/11/2025 31.317,98 IG PROJETO CRÉDITO 40.690.212/001-90",
    "BANCO BRASIL 18/11/2025 23.750,00 RONDINELE CREDITO ALMIR 914.825.425-87",
    "BANCO BRASIL 05/12/2025 5.000,00 EMPRESA XYZ CRÉDITOS 12.345.678/0001-90",
    "BANCO BRASIL 06/12/2025 1.200,00 FORNECEDOR ABC CREDITOS 98.765.432/0001-10",
    "BANCO BRASIL 07/12/2025 3.500,00 ACREDITO LTDA SERVICOS 11.222.333/0001-44",
    "BANCO BRASIL 08/12/2025 2.100,00 EMPRESA TESTE ALUGUEL 55.666.777/0001-88",
];

$regex = '/\b(créditos?|creditos?)\b/ui';

echo "Testing regex: $regex\n\n";

foreach ($testLines as $index => $line) {
    $shouldSkip = preg_match($regex, $line);
    $status = $shouldSkip ? "[SKIP]" : "[PROCESS]";
    echo "$status Line " . ($index + 1) . ": " . substr($line, 0, 80) . "\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "Lines 1-4 should be SKIPPED (contain crédito/credito/créditos/creditos)\n";
echo "Lines 5-6 should be PROCESSED (line 5 has 'ACREDITO' which is different, line 6 has no keyword)\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
