<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Loading $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    for ($row = 38; $row <= 42; $row++) {
        echo "Row $row: ";
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $val = (string) $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            if ($val !== '') {
                echo "[Col $col: $val] ";
            }
        }
        echo "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
