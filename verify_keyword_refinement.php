<?php

// Test cases for keyword filtering - ABSOLUTE PRECEDENCE
$testCases = [
    ['line' => 'TRANSF VENDA DE MERCADORIA', 'expected' => 'IGNORE'], // Transf wins now
    ['line' => 'TRANSF ENTRE CONTAS', 'expected' => 'IGNORE'],
    ['line' => 'TRANSFERENCIA RECEBIDA', 'expected' => 'IGNORE'],
    ['line' => 'TRANSFERÊNCIA RECEBIDA', 'expected' => 'IGNORE'],
    ['line' => 'VENDA A VISTA', 'expected' => 'INCLUDE'],
    ['line' => 'CRÉDITO EM CONTA', 'expected' => 'IGNORE'],
    ['line' => 'CREDITO RECEBIDO', 'expected' => 'IGNORE'],
    ['line' => 'PRESTAÇÃO DE SERVIÇO', 'expected' => 'INCLUDE (Normal expense)'],
];

echo "Testing ABSOLUTE PRECEDENCE Keyword Filtering Logic...\n\n";

// PdfParserService Logic Mock
function pdfParserTest($line)
{
    // New Logic: Forbidden keywords have absolute precedence
    if (preg_match('/\b(créditos?|creditos?|transf\.?|transferências?|transferencia)\b/ui', $line)) {
        return "IGNORE";
    }
    return "INCLUDE";
}

// MappingService Logic Mock
function mappingServiceTest($line)
{
    $name = mb_strtoupper($line);
    // New Logic: Forbidden keywords have absolute precedence
    $isTransf = str_contains($name, 'TRANSF') || str_contains($name, 'TRANSFERÊNCIA') || str_contains($name, 'TRANSFERENCIA') || str_contains($name, 'CRÉDITO') || str_contains($name, 'CREDITO');

    if ($isTransf) {
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
