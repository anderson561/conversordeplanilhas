<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Loading $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();

    echo "Worksheet: " . $worksheet->getTitle() . "\n";
    echo "Highest Row: $highestRow\n";

    $count = 0;
    for ($row = 1; $row <= $highestRow && $count < 50; $row++) {
        $rowData = [];
        $hasValue = false;
        foreach ($worksheet->getRowIterator($row, $row) as $r) {
            $cellIterator = $r->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $val = (string) $cell->getValue();
                if (trim($val) !== '')
                    $hasValue = true;
                $rowData[] = $val;
            }
        }
        if ($hasValue) {
            echo "Row $row: " . implode(" | ", $rowData) . "\n";
            $count++;
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
