<?php

use App\Services\PdfParserService;
use App\Services\MappingService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pdfParser = app(PdfParserService::class);
$mappingService = app(MappingService::class);

echo "Testing CNPJ Capture and Duplicate Removal...\n";

// --- Case 1: PDF with long Venda description (CNPJ on next line) ---
$pdfLines = [
    "22/12/2025 133.333,33 VENDA DOS APARTAMENTOS RESIDENCIAIS 1307",
    "E 1308 DO EMPREENDIMENTO VIEW (PAC. 002/006) PATRIM",
    "DETALHES COMPLEMENTARES ... 33.264.199/0001-14",
    "26/12/2025 133.333,33 TRANSF. ENTRE CONTAS REF. CRÉDITO VENDA"
];

// Mocking reflection if needed, but PdfParserService::getRowsFromLines is public if I make it or just call parse.
// Actually PdfParserService::parse is public.
$refl = new ReflectionClass($pdfParser);
$method = $refl->getMethod('getRowsFromLines');
$method->setAccessible(true);
$pdfRows = $method->invoke($pdfParser, $pdfLines);

echo "\nPDF Parsing Results:\n";
foreach ($pdfRows as $r) {
    echo "Row: Data='{$r['Data']}', Name='{$r['Razao Social']}', CNPJ='{$r['CNPJ']}'\n";
}

// --- Case 2: MappingService with CNPJ in Name column ---
$excelRows = [
    [
        'Data' => '22/12/2025',
        'Valor' => '133.333,33',
        'Historico' => 'VENDA CLIENTE XPTO 123.456.789-00'
    ],
    [
        'Data' => '26/12/2025',
        'Valor' => '133.333,33',
        'Historico' => 'TRANSF. ENTRE CONTAS REF VENDA'
    ]
];

$mappingRules = ['Data' => 'Data', 'Valor' => 'Valor', 'Razao Social' => 'Historico'];
$mappedRows = $mappingService->mapRowsToRps($excelRows, $mappingRules);

echo "\nMappingService Results:\n";
foreach ($mappedRows as $rps) {
    echo "RPS: Name='{$rps->tomador->razaoSocial}', CNPJ='{$rps->tomador->cpfCnpj}'\n";
}
