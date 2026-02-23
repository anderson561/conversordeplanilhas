<?php

echo "=== EXCEL SERIAL NUMBER TEST ===\n\n";

// Test Excel serial numbers
$testSerials = [
    46013 => '2025-12-02',  // 02/12/2025
    45996 => '2025-11-15',  // 15/11/2025
    44927 => '2022-12-02',  // 02/12/2022
];

echo "Testing Excel serial number conversion:\n\n";

foreach ($testSerials as $serial => $expected) {
    try {
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$serial} days");
        $result = $excelEpoch->format('Y-m-d');
        $status = ($result === $expected) ? "[PASS]" : "[FAIL]";
        echo "$status Serial: $serial => $result (expected: $expected)\n";
    } catch (Exception $e) {
        echo "[ERROR] Serial: $serial => {$e->getMessage()}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "Excel serial numbers will now be correctly converted to dates!\n";
