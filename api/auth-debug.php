<?php
// Debug version with detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture all output
ob_start();

try {
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    echo "Step 1: Headers set\n";

    require_once '../config/database.php';
    echo "Step 2: Database included\n";

    require_once '../config/session.php';
    echo "Step 3: Session included\n";

    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'check_session') {
        echo "Step 4: Check session action\n";
        
        startSession();
        echo "Step 5: Session started\n";
        
        if (isset($_SESSION['user_id'])) {
            echo "Step 6: User found in session\n";
            
            $user_data = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'full_name' => $_SESSION['full_name'] ?? '',
                'role' => $_SESSION['role'] ?? ''
            ];
            
            echo "Step 7: User data prepared\n";
            
            // Clean output and send JSON
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'User is logged in',
                'data' => $user_data,
                'debug' => 'All steps completed successfully'
            ]);
        } else {
            echo "Step 6: No user in session\n";
            
            // Clean output and send JSON
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'No active session',
                'debug' => 'No user_id in session'
            ]);
        }
    } else {
        echo "Step 4: Invalid request\n";
        
        // Clean output and send JSON
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request',
            'debug' => 'Not a check_session GET request'
        ]);
    }
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    // Clean output and send error JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Error $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    // Clean output and send error JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// End output buffering
ob_end_flush();
?>
