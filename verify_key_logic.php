<?php

require __DIR__ . '/vendor/autoload.php';

function calculateMod11(string $chave): int
{
    $peso = 2;
    $soma = 0;

    for ($i = strlen($chave) - 1; $i >= 0; $i--) {
        $soma += intval($chave[$i]) * $peso;
        $peso = ($peso == 9) ? 2 : $peso + 1;
    }

    $resto = $soma % 11;
    $dv = ($resto == 0 || $resto == 1) ? 0 : 11 - $resto;

    return $dv;
}

// Generate a sample Model 44 key
$uf = '29'; // BA
$aamm = '2511'; // Nov 2025
$cnpj = '12314872000103';
$mod = '44';
$serie = '001';
$nNF = '000000001';
$tpEmis = '1';
$cNF = '12345678';

$baseKey = $uf . $aamm . $cnpj . $mod . $serie . $nNF . $tpEmis . $cNF;
$dv = calculateMod11($baseKey);
$fullKey = $baseKey . $dv;

echo "Generated Key (Mod 44): $fullKey\n";
echo "Length: " . strlen($fullKey) . "\n";
echo "DV: $dv\n";

// Validate consistency
if (strlen($fullKey) !== 44) {
    echo "ERROR: Invalid length!\n";
} else {
    echo "SUCCESS: Length is correct.\n";
}

// Check XML Consistency Hypothesis
echo "\n--- XML Consistency Check ---\n";
echo "XML cNF Tag matches Key? " . ($cNF === '12345678' ? 'YES' : 'NO') . "\n";
echo "Simulating Empty cNF Tag...\n";
echo "Validator sees Key: $fullKey\n";
echo "Validator reads <cNF>: (empty)\n";
echo "Validator PREDICTS cNF must be: $cNF (from position 36-43 of key)\n";
echo "MISMATCH EXPECTED!\n";
