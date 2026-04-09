<?php
// Simple syntax test
try {
    include 'api/service_requests.php';
    echo "Syntax: OK\n";
} catch (ParseError $e) {
    echo "Parse Error: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}
?>
