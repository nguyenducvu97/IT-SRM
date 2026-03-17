<?php
// Debug user role in database
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "=== DEBUG USER ROLE ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "Current user ID: " . $user_id . "\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check user in database
    $stmt = $db->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Database user data:\n";
        echo "  ID: " . $user['id'] . "\n";
        echo "  Username: " . $user['username'] . "\n";
        echo "  Full Name: " . $user['full_name'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
        
        echo "Session role: " . ($_SESSION['role'] ?? 'NULL') . "\n";
        echo "getCurrentUserRole(): " . getCurrentUserRole() . "\n";
        
        if ($user['role'] !== $_SESSION['role']) {
            echo "❌ ROLE MISMATCH! Database role != Session role\n";
        } else {
            echo "✅ Role matches database\n";
        }
    } else {
        echo "❌ User not found in database\n";
    }
} else {
    echo "❌ No user in session\n";
}
?>
