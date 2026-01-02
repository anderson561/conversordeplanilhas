<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\PdfParserService;

// Test if PDF parser is working
$pdfPath = __DIR__ . '/storage/app/uploads/test.pdf'; // You'll need to update this path

if (!file_exists($pdfPath)) {
    echo "PDF file not found at: $pdfPath\n";
    echo "Please upload a PDF first and update the path in this script.\n";
    exit(1);
}

echo "Testing PDF Parser...\n";
echo "File: $pdfPath\n";
echo "Extension: " . pathinfo($pdfPath, PATHINFO_EXTENSION) . "\n";
echo "Mime Type (finfo): " . mime_content_type($pdfPath) . "\n\n";

try {
    $parser = new PdfParserService();
    $rows = $parser->parse($pdfPath);

    echo "Parsed " . count($rows) . " rows\n\n";

    if (count($rows) > 0) {
        echo "First row:\n";
        print_r($rows[0]);

        echo "\nAll rows:\n";
        foreach ($rows as $i => $row) {
            echo "Row " . ($i + 1) . ": ";
            echo "Data={$row['Data']}, Valor={$row['Valor']}, Razao Social={$row['Razao Social']}, CNPJ={$row['CNPJ']}\n";
        }
    } else {
        echo "No rows parsed! Check the regex pattern.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
