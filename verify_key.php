<?php

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

$keys = [
    '29251225311856000109440010002471001718585971',
    '29251225311856000109440010002471011500440266',
    '29251225311856000109440010002471021909723346'
];

foreach ($keys as $fullKey) {
    if (strlen($fullKey) !== 44) {
        echo "Key length error: " . strlen($fullKey) . "\n";
        continue;
    }
    $base = substr($fullKey, 0, 43);
    $dv = substr($fullKey, 43, 1);

    $calculated = calculateMod11($base);

    echo "Key: $fullKey\n";
    echo "Base: $base\n";
    echo "Provided DV: $dv\n";
    echo "Calculated DV: $calculated\n";
    echo ($dv == $calculated ? "VALID" : "INVALID") . "\n";
    echo "-------------------\n";
}
