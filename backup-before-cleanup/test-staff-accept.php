<?php
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

echo "<h1>Test Staff Accept Request Functionality</h1>";

// Get current user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';
$user_name = $_SESSION['full_name'] ?? 'Unknown';

echo "<p><strong>Current User:</strong> $user_name (ID: $user_id, Role: $user_role)</p>";

// Check if user is staff or admin
if ($user_role !== 'staff' && $user_role !== 'admin') {
    die("<p style='color: red;'>Access denied: Only staff and admin can test accept functionality</p>");
}

// Find requests that can be accepted (open and unassigned)
try {
    $db = (new Database())->getConnection();
    
    $query = "SELECT sr.id, sr.title, sr.status, sr.assigned_to, sr.created_at,
                     u.full_name as requester_name
              FROM service_requests sr
              LEFT JOIN users u ON sr.user_id = u.id
              WHERE sr.status = 'open' AND (sr.assigned_to IS NULL OR sr.assigned_to = 0)
              ORDER BY sr.created_at DESC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Available Requests for Acceptance</h2>";
    
    if (empty($requests)) {
        echo "<p>No requests available for acceptance. All requests are either assigned or not in 'open' status.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>
                <th>ID</th>
                <th>Title</th>
                <th>Requester</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Action</th>
              </tr>";
        
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>" . htmlspecialchars($request['requester_name']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>" . ($request['assigned_to'] ? $request['assigned_to'] : 'None') . "</td>";
            echo "<td>
                    <form method='POST' style='margin: 0;'>
                        <input type='hidden' name='request_id' value='{$request['id']}'>
                        <button type='submit' name='accept_request' 
                                style='background-color: #28a745; color: white; padding: 5px 10px; border: none; cursor: pointer;'>
                            <i class='fas fa-check'></i> Nhận yêu cầu
                        </button>
                    </form>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Handle accept request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
    $request_id = (int)$_POST['request_id'];
    
    if ($request_id <= 0) {
        echo "<p style='color: red;'>Invalid request ID</p>";
    } else {
        try {
            $db = (new Database())->getConnection();
            
            // Check if request is still available
            $check_query = "SELECT id, assigned_to, status FROM service_requests 
                           WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 
                           AND (assigned_to IS NULL OR assigned_to = 0)";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                echo "<p style='color: red;'>Request not available for assignment</p>";
            } else {
                // Update request to assign to staff
                $update_query = "UPDATE service_requests 
                                SET assigned_to = :user_id, status = 'in_progress', 
                                    assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                                WHERE id = :request_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":request_id", $request_id);
                $update_stmt->bindParam(":user_id", $user_id);
                
                if ($update_stmt->execute()) {
                    echo "<p style='color: green; font-weight: bold;'>✅ Request #$request_id accepted successfully!</p>";
                    
                    // Show updated request info
                    $updated_query = "SELECT sr.*, u.full_name as requester_name 
                                      FROM service_requests sr
                                      LEFT JOIN users u ON sr.user_id = u.id
                                      WHERE sr.id = :request_id";
                    $updated_stmt = $db->prepare($updated_query);
                    $updated_stmt->bindParam(":request_id", $request_id);
                    $updated_stmt->execute();
                    $updated_request = $updated_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($updated_request) {
                        echo "<div style='background-color: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                        echo "<h3>Updated Request Details:</h3>";
                        echo "<p><strong>ID:</strong> {$updated_request['id']}</p>";
                        echo "<p><strong>Title:</strong> " . htmlspecialchars($updated_request['title']) . "</p>";
                        echo "<p><strong>Status:</strong> {$updated_request['status']}</p>";
                        echo "<p><strong>Assigned To:</strong> $user_name (ID: $user_id)</p>";
                        echo "<p><strong>Accepted At:</strong> {$updated_request['accepted_at']}</p>";
                        echo "</div>";
                    }
                    
                    // Test notification functionality
                    echo "<h3>Testing Notification System</h3>";
                    try {
                        require_once 'lib/ServiceRequestNotificationHelper.php';
                        $notificationHelper = new ServiceRequestNotificationHelper();
                        
                        // Test user notification
                        $userNotifResult = $notificationHelper->notifyUserRequestInProgress(
                            $request_id, 
                            $updated_request['user_id'], 
                            $user_name
                        );
                        echo "<p>📧 User notification: " . ($userNotifResult ? "✅ Sent" : "❌ Failed") . "</p>";
                        
                        // Test admin notification
                        $adminNotifResult = $notificationHelper->notifyAdminStatusChange(
                            $request_id, 
                            'open', 
                            'in_progress', 
                            $user_name, 
                            $updated_request['title']
                        );
                        echo "<p>📧 Admin notification: " . ($adminNotifResult ? "✅ Sent" : "❌ Failed") . "</p>";
                        
                    } catch (Exception $e) {
                        echo "<p style='color: orange;'>⚠️ Notification test failed: " . $e->getMessage() . "</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>Failed to accept request</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
        }
    }
}

// Show current session info for debugging
echo "<h3>Session Debug Info</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
echo "Full Name: " . ($_SESSION['full_name'] ?? 'Not set') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "</pre>";

echo "<hr>";
echo "<p><a href='index.html'>← Back to Main Application</a></p>";
echo "<p><a href='request-detail.html'>← Go to Request Detail Page</a></p>";
?>
