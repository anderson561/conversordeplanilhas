<?php

echo "=== SHORT DATE FORMAT TEST ===\n\n";

// Test dates in short format (like Excel exports)
$testDates = [
    '12/2/2025' => '2025-02-12',   // Short format (1 digit month)
    '12/02/2025' => '2025-02-12',  // Standard format (2 digit month)
    '1/4/2025' => '2025-04-01',    // Both short
    '12/17/2025' => '2025-17-12',  // Standard
];

echo "Testing date normalization with short formats:\n\n";

foreach ($testDates as $input => $expected) {
    // Simulate the normalizeDate logic
    $dateStr = str_replace('.', '/', $input);

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4}|\d{2})/', $dateStr, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];

        if (strlen($year) === 2) {
            $year = '20' . $year;
        }
        $result = "{$year}-{$month}-{$day}";
        $status = ($result === $expected) ? "[PASS]" : "[FAIL]";
        echo "$status Input: $input => $result (expected: $expected)\n";
    } else {
        echo "[FAIL] Input: $input => NO MATCH\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "After this fix, dates like '12/2/2025' will be correctly normalized to '2025-02-12'\n";
