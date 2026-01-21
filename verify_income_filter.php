<?php

echo "=== EXPANDED INCOME FILTER VERIFICATION ===\n\n";

// Test cases for expanded keywords
$testLines = [
    "BANCO BRASIL 04/11/2025 31.317,98 IG PROJETO CRÉDITO 40.690.212/001-90",
    "BANCO BRASIL 18/11/2025 23.750,00 RONDINELE CREDITOS ALMIR 914.825.425-87",
    "BANCO BRASIL 05/12/2025 5.000,00 EMPRESA XYZ TRANSF 12.345.678/0001-90",
    "BANCO BRASIL 06/12/2025 1.200,00 FORNECEDOR ABC TRANSF. 98.765.432/0001-10",
    "BANCO BRASIL 07/12/2025 3.500,00 CLIENTE DEF TRANSFERÊNCIA 11.222.333/0001-44",
    "BANCO BRASIL 08/12/2025 2.100,00 EMPRESA GHI TRANSFERÊNCIAS 55.666.777/0001-88",
    "BANCO BRASIL 09/12/2025 1.800,00 FORNECEDOR JKL ALUGUEL 99.888.777/0001-66",
    "BANCO BRASIL 10/12/2025 4.200,00 TRANSFORMAÇÃO LTDA SERVICOS 22.333.444/0001-55",
];

$regex = '/\b(créditos?|creditos?|transf\.?|transferências?)\b/ui';

echo "Testing regex: $regex\n\n";

foreach ($testLines as $index => $line) {
    $shouldSkip = preg_match($regex, $line);
    $status = $shouldSkip ? "[SKIP]" : "[PROCESS]";
    echo "$status Line " . ($index + 1) . ": " . substr($line, 0, 80) . "\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "Lines 1-6 should be SKIPPED (contain income/transfer keywords)\n";
echo "Lines 7-8 should be PROCESSED (line 7 has 'ALUGUEL', line 8 has 'TRANSFORMAÇÃO' which is different)\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
