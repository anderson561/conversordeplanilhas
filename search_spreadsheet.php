<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';

$filePath = 'c:/Users/ANDERSON/php/storage/app/private/uploads/S5ox2AURfpFShowcbWGiscAk3Z8fMUuUCuNAY1Lj.xlsx';

echo "Searching for data in $filePath...\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    $found = false;
    for ($r = 1; $r <= $highestRow; $r++) {
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $val = (string) $worksheet->getCellByColumnAndRow($c, $r)->getValue();
            if (stripos($val, 'CNPJ') !== false || stripos($val, 'CLIENTE') !== false || stripos($val, 'NOME') !== false) {
                echo "Found keyword '$val' at Row $r, Col $c\n";
                $found = true;
            }
        }
    }

    if (!$found)
        echo "No specific keywords found.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
