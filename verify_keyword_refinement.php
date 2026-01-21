<?php

// Test cases for keyword filtering
$testCases = [
    ['line' => 'TRANSF VENDA DE MERCADORIA', 'expected' => 'INCLUDE (Venda wins)'],
    ['line' => 'TRANSF ENTRE CONTAS', 'expected' => 'IGNORE'],
    ['line' => 'TRANSFERENCIA RECEBIDA', 'expected' => 'IGNORE'],
    ['line' => 'TRANSFERÊNCIA RECEBIDA', 'expected' => 'IGNORE'],
    ['line' => 'VENDA A VISTA', 'expected' => 'INCLUDE'],
    ['line' => 'CRÉDITO EM CONTA', 'expected' => 'IGNORE'],
    ['line' => 'CREDITO RECEBIDO', 'expected' => 'IGNORE'],
    ['line' => 'PRESTAÇÃO DE SERVIÇO', 'expected' => 'INCLUDE (Normal expense)'],
];

echo "Testing Keyword Filtering Logic...\n\n";

// PdfParserService Logic Mock
function pdfParserTest($line)
{
    $isVenda = preg_match('/\bvendas?\b/ui', $line);
    $isIgnore = preg_match('/\b(créditos?|creditos?|transf\.?|transferências?|transferencia)\b/ui', $line);

    if ($isIgnore && !$isVenda) {
        return "IGNORE";
    }
    return "INCLUDE";
}

// MappingService Logic Mock
function mappingServiceTest($line)
{
    $name = mb_strtoupper($line);
    $isVenda = str_contains($name, 'VENDA');
    $isTransf = str_contains($name, 'TRANSF') || str_contains($name, 'TRANSFERÊNCIA') || str_contains($name, 'TRANSFERENCIA') || str_contains($name, 'CRÉDITO') || str_contains($name, 'CREDITO');

    if ($isTransf && !$isVenda) {
        return "IGNORE";
    }
    return "INCLUDE";
}

foreach ($testCases as $case) {
    $pdfResult = pdfParserTest($case['line']);
    $mappingResult = mappingServiceTest($case['line']);

    $status = ($pdfResult === ($case['expected'] === 'IGNORE' ? 'IGNORE' : 'INCLUDE')) ? "✅" : "❌";
    echo sprintf(
        "[%s] Line: %-30s | Expected: %-20s | PDF: %-8s | Mapping: %-8s\n",
        $status,
        $case['line'],
        $case['expected'],
        $pdfResult,
        $mappingResult
    );
}
