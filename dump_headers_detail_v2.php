<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Loading $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    for ($rowNum = 38; $rowNum <= 42; $rowNum++) {
        echo "Row $rowNum: ";
        foreach ($worksheet->getRowIterator($rowNum, $rowNum) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $colIndex = 1;
            foreach ($cellIterator as $cell) {
                $val = (string) $cell->getValue();
                if ($val !== '') {
                    echo "[Col $colIndex: $val] ";
                }
                $colIndex++;
            }
        }
        echo "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
