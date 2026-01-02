<?php
$dom = new DOMDocument('1.0', 'UTF-8');
// Test 1: createElement with special char
try {
    $el = $dom->createElement('test', 'A & B');
    $dom->appendChild($el);
    echo "Result 1: " . $dom->saveXML() . "\n";
} catch (Throwable $e) {
    echo "Error 1: " . $e->getMessage() . "\n";
}

// Test 2: createTextNode
$el2 = $dom->createElement('test2');
$el2->appendChild($dom->createTextNode('A & B'));
$dom->appendChild($el2);
echo "Result 2: " . $dom->saveXML() . "\n";
