<?php
// Debug script for departments API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEPARTMENTS API DEBUG ===\n";

// Test database connection first
require_once 'config/database.php';

echo "1. Testing database connection...\n";
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo === null) {
        echo "ERROR: Database connection returned null\n";
        exit;
    }
    
    echo "SUCCESS: Database connection established\n";
    
    // Test if departments table exists
    echo "2. Testing departments table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'departments'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "SUCCESS: Departments table exists\n";
        
        // Test if table has data
        echo "3. Testing departments data...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Found $count departments in table\n";
        
        // Test the actual query used by dropdown
        echo "4. Testing dropdown query...\n";
        $stmt = $pdo->prepare("SELECT name FROM departments WHERE is_active = TRUE ORDER BY name");
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Dropdown query results:\n";
        print_r($departments);
        
    } else {
        echo "ERROR: Departments table does not exist\n";
        
        // Show available tables
        echo "Available tables:\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        print_r($tables);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Test API endpoint directly
echo "\n5. Testing API endpoint...\n";
echo "URL: http://localhost/it-service-request/api/departments.php?action=dropdown\n";

// Use curl to test the endpoint
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/departments.php?action=dropdown');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "HTTP Status: $http_code\n";
    if ($error) {
        echo "CURL Error: $error\n";
    } else {
        echo "Response: $response\n";
    }
} else {
    echo "CURL not available\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
