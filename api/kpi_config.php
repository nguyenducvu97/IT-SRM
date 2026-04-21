<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
try {
    require_once 'config.php';
    $db = getDB();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Check if user is admin (optional - remove if not needed)
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
//     exit();
// }

$action = $_GET['action'] ?? '';

try {

    switch ($action) {
        case 'get_config':
            getKPIConfig($db);
            break;
        case 'save_config':
            saveKPIConfig($db);
            break;
        case 'reset_config':
            resetKPIConfig($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("KPI Config Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function getKPIConfig($db) {
    // Check if kpi_config table exists
    $check_table = "SHOW TABLES LIKE 'kpi_config'";
    $result = $db->query($check_table);
    
    if ($result->rowCount() == 0) {
        // Create table and insert default config
        createKPIConfigTable($db);
    }
    
    $query = "SELECT * FROM kpi_config ORDER BY kpi_type";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $configs]);
}

function saveKPIConfig($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        foreach ($input as $config) {
            $query = "UPDATE kpi_config SET 
                     formula = :formula, 
                     description = :description, 
                     weight_percentage = :weight_percentage,
                     updated_at = NOW()
                     WHERE kpi_type = :kpi_type";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':formula', $config['formula']);
            $stmt->bindParam(':description', $config['description']);
            $stmt->bindParam(':weight_percentage', $config['weight_percentage']);
            $stmt->bindParam(':kpi_type', $config['kpi_type']);
            $stmt->execute();
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'KPI configuration updated successfully']);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Save KPI Config Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save configuration']);
    }
}

function resetKPIConfig($db) {
    try {
        $db->beginTransaction();
        
        // Delete existing config
        $db->exec("DELETE FROM kpi_config");
        
        // Insert default config
        insertDefaultKPIConfig($db);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'KPI configuration reset to default']);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Reset KPI Config Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reset configuration']);
    }
}

function createKPIConfigTable($db) {
    $create_table = "CREATE TABLE IF NOT EXISTS kpi_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kpi_type VARCHAR(50) NOT NULL UNIQUE,
        formula TEXT NOT NULL,
        description TEXT,
        weight_percentage DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($create_table);
    insertDefaultKPIConfig($db);
}

function insertDefaultKPIConfig($db) {
    $default_configs = [
        [
            'kpi_type' => 'K1',
            'formula' => '=MAX(1; MIN(5; 5 - (L2/30)))',
            'description' => 'L2 = Thoi gian phan hoi (phut)',
            'weight_percentage' => 15.00
        ],
        [
            'kpi_type' => 'K2',
            'formula' => '=MAX(1; MIN(5; 5 - (M2/24)))',
            'description' => 'M2 = Thoi gian hoan thanh (gio)',
            'weight_percentage' => 35.00
        ],
        [
            'kpi_type' => 'K3',
            'formula' => '=MAX(1; MIN(5; N2))',
            'description' => 'N2 = Danh gia chung (1-5)',
            'weight_percentage' => 40.00
        ],
        [
            'kpi_type' => 'K4',
            'formula' => '=MAX(1; MIN(5; O2/20))',
            'description' => 'O2 = Danh gia staff xu ly yeu cau',
            'weight_percentage' => 10.00
        ],
        [
            'kpi_type' => 'TOTAL',
            'formula' => '=(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)',
            'description' => 'P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)',
            'weight_percentage' => 100.00
        ]
    ];
    
    foreach ($default_configs as $config) {
        $query = "INSERT INTO kpi_config (kpi_type, formula, description, weight_percentage) 
                 VALUES (:kpi_type, :formula, :description, :weight_percentage)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':kpi_type', $config['kpi_type']);
        $stmt->bindParam(':formula', $config['formula']);
        $stmt->bindParam(':description', $config['description']);
        $stmt->bindParam(':weight_percentage', $config['weight_percentage']);
        $stmt->execute();
    }
}
?>
