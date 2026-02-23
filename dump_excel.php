<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Loading $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();

    echo "Worksheet: " . $worksheet->getTitle() . "\n";
    echo "Dimensions: 1:$highestRow / A:$highestColumn\n";

    for ($row = 1; $row <= min(20, $highestRow); $row++) {
        echo "Row $row: ";
        $rowData = [];
        foreach ($worksheet->getRowIterator($row, $row) as $r) {
            $cellIterator = $r->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $rowData[] = (string) $cell->getValue();
            }
        }
        echo implode(" | ", $rowData) . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
