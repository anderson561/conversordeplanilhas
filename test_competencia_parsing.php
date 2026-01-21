<?php

echo "=== COMPETÊNCIA DATE PARSING TEST ===\n\n";

// Test competência parsing logic
$testCases = [
    '202602' => '2026-02-01', // February 2026
    '202603' => '2026-03-01', // March 2026
    '202604' => '2026-04-01', // April 2026
    '202512' => '2025-12-01', // December 2025
    'invalid' => null,         // Should fail
];

echo "Testing Competência (AAAAMM) to Date conversion:\n\n";

foreach ($testCases as $competencia => $expected) {
    if (preg_match('/^(\d{4})(\d{2})$/', $competencia, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $result = "$year-$month-01";
        $status = ($result === $expected) ? "[PASS]" : "[FAIL]";
        echo "$status Competência: $competencia => $result (expected: $expected)\n";
    } else {
        $status = ($expected === null) ? "[PASS]" : "[FAIL]";
        echo "$status Competência: $competencia => INVALID (expected: " . ($expected ?? 'null') . ")\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
