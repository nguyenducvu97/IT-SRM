<?php
// Direct test of close request logic
require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Mock user
$_SESSION['user_id'] = 4;
$_SESSION['role'] = 'user';

echo "Testing Close Request Logic Directly...\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Test data
$request_id = 5;
$rating = 5;
$feedback = 'Great service! Very satisfied.';
$software_feedback = 'The system is easy to use.';
$would_recommend = 'yes';
$ease_of_use = 5;
$speed_stability = 4;
$requirement_meeting = 5;

echo "\nTesting database operations...\n";

try {
    // Check if request exists and belongs to current user
    $check_query = "SELECT id, user_id, status FROM service_requests WHERE id = :request_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":request_id", $request_id);
    $check_stmt->execute();
    
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "❌ Request not found\n";
        exit;
    }
    
    echo "✅ Request found: ID={$request['id']}, User={$request['user_id']}, Status={$request['status']}\n";
    
    // Check permissions
    if ($_SESSION['role'] === 'user' && $request['user_id'] != $_SESSION['user_id']) {
        echo "❌ Access denied. You can only close your own requests.\n";
        exit;
    }
    
    echo "✅ Permission check passed\n";
    
    // Check if request is in a status that can be closed
    if ($request['status'] !== 'resolved') {
        echo "❌ Only resolved requests can be closed. Current status: {$request['status']}\n";
        exit;
    }
    
    echo "✅ Request status check passed\n";
    
    // Start transaction
    $db->beginTransaction();
    echo "✅ Transaction started\n";
    
    // Insert feedback into request_feedback table
    $feedback_query = "INSERT INTO request_feedback 
                      (service_request_id, rating, feedback, software_feedback, would_recommend, 
                       ease_of_use, speed_stability, requirement_meeting, created_by) 
                      VALUES (:request_id, :rating, :feedback, :software_feedback, :would_recommend,
                              :ease_of_use, :speed_stability, :requirement_meeting, :created_by)";
    $feedback_stmt = $db->prepare($feedback_query);
    $feedback_stmt->bindParam(":request_id", $request_id);
    $feedback_stmt->bindParam(":rating", $rating, PDO::PARAM_INT);
    $feedback_stmt->bindParam(":feedback", $feedback);
    $feedback_stmt->bindParam(":software_feedback", $software_feedback);
    $feedback_stmt->bindParam(":would_recommend", $would_recommend);
    $feedback_stmt->bindParam(":ease_of_use", $ease_of_use, PDO::PARAM_INT);
    $feedback_stmt->bindParam(":speed_stability", $speed_stability, PDO::PARAM_INT);
    $feedback_stmt->bindParam(":requirement_meeting", $requirement_meeting, PDO::PARAM_INT);
    $feedback_stmt->bindParam(":created_by", $_SESSION['user_id']);
    
    if ($feedback_stmt->execute()) {
        echo "✅ Feedback inserted successfully\n";
    } else {
        echo "❌ Failed to insert feedback\n";
        $db->rollBack();
        exit;
    }
    
    // Update request status to closed
    $update_query = "UPDATE service_requests 
                   SET status = 'closed', 
                       closed_at = NOW(),
                       updated_at = NOW()
                   WHERE id = :request_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":request_id", $request_id);
    
    if ($update_stmt->execute()) {
        echo "✅ Request status updated to closed\n";
    } else {
        echo "❌ Failed to update request status\n";
        $db->rollBack();
        exit;
    }
    
    // Commit transaction
    $db->commit();
    echo "✅ Transaction committed\n";
    
    // Verify the changes
    $verify_query = "SELECT sr.status, sr.closed_at, rf.rating, rf.feedback 
                    FROM service_requests sr 
                    LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id 
                    WHERE sr.id = :request_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(":request_id", $request_id);
    $verify_stmt->execute();
    
    $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n✅ Verification Results:\n";
    echo "Status: {$result['status']}\n";
    echo "Closed At: {$result['closed_at']}\n";
    echo "Rating: {$result['rating']}\n";
    echo "Feedback: {$result['feedback']}\n";
    
    echo "\n🎉 Close request functionality is working correctly!\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
