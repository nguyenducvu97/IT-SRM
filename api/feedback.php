<?php
/**
 * Feedback API Endpoint
 * Handles saving and retrieving user feedback for service requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

/**
 * Save feedback for a service request
 */
function saveFeedback() {
    global $pdo;
    
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            return;
        }
        
        // Validate required fields
        $required_fields = ['service_request_id', 'rating', 'created_by'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        $service_request_id = (int)$input['service_request_id'];
        $rating = (int)$input['rating'];
        $created_by = (int)$input['created_by'];
        $feedback = $input['feedback'] ?? null;
        $software_feedback = $input['software_feedback'] ?? null;
        $ease_of_use = isset($input['ease_of_use']) ? (int)$input['ease_of_use'] : null;
        $speed_stability = isset($input['speed_stability']) ? (int)$input['speed_stability'] : null;
        $requirement_meeting = isset($input['requirement_meeting']) ? (int)$input['requirement_meeting'] : null;
        
        // Validate rating range
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            return;
        }
        
        // Check if service request exists and belongs to user or user is admin
        $check_stmt = $pdo->prepare("
            SELECT sr.id, sr.created_by 
            FROM service_requests sr 
            WHERE sr.id = ? AND (sr.created_by = ? OR ? = 1)
        ");
        $check_stmt->execute([$service_request_id, $created_by, $created_by]);
        
        if (!$check_stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Service request not found or access denied']);
            return;
        }
        
        // Check if feedback already exists for this request
        $existing_stmt = $pdo->prepare("
            SELECT id FROM request_feedback 
            WHERE service_request_id = ?
        ");
        $existing_stmt->execute([$service_request_id]);
        $existing_feedback = $existing_stmt->fetch();
        
        if ($existing_feedback) {
            // Update existing feedback
            $update_stmt = $pdo->prepare("
                UPDATE request_feedback 
                SET rating = ?, feedback = ?, software_feedback = ?, ease_of_use = ?, speed_stability = ?, requirement_meeting = ?, updated_at = CURRENT_TIMESTAMP
                WHERE service_request_id = ?
            ");
            $result = $update_stmt->execute([$rating, $feedback, $software_feedback, $ease_of_use, $speed_stability, $requirement_meeting, $service_request_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Feedback updated successfully',
                    'feedback_id' => $existing_feedback['id']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update feedback']);
            }
        } else {
            // Insert new feedback
            $insert_stmt = $pdo->prepare("
                INSERT INTO request_feedback (service_request_id, rating, feedback, software_feedback, ease_of_use, speed_stability, requirement_meeting, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $insert_stmt->execute([$service_request_id, $rating, $feedback, $software_feedback, $ease_of_use, $speed_stability, $requirement_meeting, $created_by]);
            
            if ($result) {
                $feedback_id = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Feedback saved successfully',
                    'feedback_id' => $feedback_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save feedback']);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Database error in saveFeedback: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Get feedback for a service request
 */
function getFeedback($service_request_id) {
    global $pdo;
    
    try {
        $service_request_id = (int)$service_request_id;
        
        $stmt = $pdo->prepare("
            SELECT rf.*, u.fullname as created_by_name
            FROM request_feedback rf
            LEFT JOIN users u ON rf.created_by = u.id
            WHERE rf.service_request_id = ?
            ORDER BY rf.created_at DESC
        ");
        $stmt->execute([$service_request_id]);
        
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'feedback' => $feedback
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getFeedback: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Get feedback statistics
 */
function getFeedbackStats() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_feedback,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as very_satisfied,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as satisfied,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as neutral,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as dissatisfied,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as very_dissatisfied
            FROM request_feedback
        ");
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getFeedbackStats: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        saveFeedback();
        break;
    case 'GET':
        if (isset($_GET['service_request_id'])) {
            getFeedback($_GET['service_request_id']);
        } elseif (isset($_GET['stats'])) {
            getFeedbackStats();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameter']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
