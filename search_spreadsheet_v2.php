<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Searching for data in $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    $found = false;
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $rowIndex = $row->getRowIndex();
        $colIndex = 1;
        foreach ($cellIterator as $cell) {
            $val = (string) $cell->getValue();
            if (stripos($val, 'CNPJ') !== false || stripos($val, 'CLIENTE') !== false || stripos($val, 'NOME') !== false || stripos($val, 'CPF') !== false) {
                echo "Found keyword '$val' at Row $rowIndex, Col $colIndex\n";
                $found = true;
            }
            $colIndex++;
        }
        if ($rowIndex > 200)
            break; // Limit search
    }

    if (!$found)
        echo "No specific keywords found in first 200 rows.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
