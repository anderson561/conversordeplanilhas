<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\MappingService;
use App\Services\XmlGeneratorNFeService;

// Mock dependencies
$parser = new \App\Services\FileParserService();
$xmlGen = new \App\Services\XmlGeneratorService();
$csvGen = new \App\Services\CsvGeneratorService();

$mappingService = new MappingService($parser);
$nfeService = new XmlGeneratorNFeService();

// test data
$date1 = "19/11/2025";
$date2 = "2025-11-20";
$value1 = "7.000,00";
$value2 = "71.313,81";
$value3 = "1234.56";

echo "Testing Value Parsing:\n";
foreach ([$value1, $value2, $value3] as $v) {
    // Manually testing the mapping logic (via reflection or just raw logic)
    $cleanValue = function ($valorStr) {
        if (str_contains($valorStr, ',')) {
            $valorClean = str_replace('.', '', $valorStr);
            $valorClean = str_replace(',', '.', $valorClean);
            $valorClean = preg_replace('/[^0-9\.-]/', '', $valorClean);
        } else {
            $valorClean = preg_replace('/[^0-9\.-]/', '', $valorStr);
        }
        return (float) $valorClean;
    };
    echo "Raw: $v -> Parsed: " . $cleanValue($v) . "\n";
}

echo "\nTesting Date Normalization:\n";
$normalizeDate = function ($date) {
    if (!$date)
        return null;
    $dateStr = trim((string) $date);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateStr, $matches)) {
        return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $dateStr, $matches)) {
        return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
    }
    $ts = strtotime($dateStr);
    return $ts ? date('Y-m-d', $ts) : null;
};

foreach ([$date1, $date2] as $d) {
    $norm = $normalizeDate($d);
    echo "Raw: $d -> Normalized: $norm\n";

    // Test XML Gen flexible parsing
    $parseFlexible = function ($dateStr) {
        $dt = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $dateStr)) {
            $dt = \DateTime::createFromFormat('d/m/Y', substr($dateStr, 0, 10));
        }
        if (!$dt) {
            $ts = strtotime($dateStr);
            if ($ts)
                $dt = new \DateTime("@$ts");
        }
        return $dt ? $dt->format('Y-m-d') . 'T12:00:00-03:00' : 'FAIL';
    };

    echo "   XML <dhEmi>: " . $parseFlexible($norm) . "\n";
}
