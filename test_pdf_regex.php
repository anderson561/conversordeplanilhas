<?php

$lines = [
    "O PERFUMISTA - R$ 12.511,13", // Problematic line
    "BANCO BRASIL 04/11/2025 31.317,98IG PROJETO, CONSULTORIA E ENTRETENIMENTO LTDA ALUGUEL IMOVEL 40.690.212/001-90", // Pattern 4
    "01/07/2025 2.160,00 Sample Name 12.345.678/0001-90" // Pattern 1
];

echo "Testing Regex Patterns...\n\n";

foreach ($lines as $line) {
    echo "Line: $line\n";
    $matched = false;

    // Pattern 1
    if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+([\d\.,]+(?:R\$)?)\s+(.+?)\s+(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/u', $line, $matches)) {
        echo "  [MATCH] Pattern 1\n";
        $matched = true;
    }

    // Pattern 2
    if (preg_match('/(.+?)\s+(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+([\d\.,]+(?:R\$)?)\s+(\d{2}\/\d{2}\/\d{4})/u', $line, $matches)) {
        echo "  [MATCH] Pattern 2\n";
        $matched = true;
    }

    // Pattern 3 (Current)
    if (preg_match('/(.+?)\s+([\d\.,]+)R\$/u', $line, $matches)) {
        echo "  [MATCH] Pattern 3 (Suffixed R$)\n";
        $matched = true;
    }

    // New Pattern Candidate for "Name - R$ Value"
    if (preg_match('/(.+?)\s+-\s+R\$\s*([\d\.,]+)/u', $line, $matches)) {
        echo "  [MATCH] New Pattern Candidate (Name - R$ Value)\n";
        print_r($matches);
        $matched = true;
    }

    if (!$matched) {
        echo "  [FAIL] No match found.\n";
    }
    echo "--------------------------------------------------\n";
}
