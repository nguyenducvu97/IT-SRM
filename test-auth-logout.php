<?php
// Test authentication and logout flow
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Authentication & Logout Debug Test</h1>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Session Handler
echo "<h2>Test 2: Session Handler</h2>";
try {
    require_once 'config/session.php';
    startSession();
    echo "✅ Session started successfully<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: " . json_encode($_SESSION) . "<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

// Test 3: Check Session Table
echo "<h2>Test 3: Check Sessions Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM sessions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Sessions table exists<br>";
    echo "Total sessions: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $stmt = $db->query("SELECT id, timestamp FROM sessions ORDER BY timestamp DESC LIMIT 5");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<br>Recent sessions:<br>";
        echo "<pre>";
        print_r($sessions);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Sessions table error: " . $e->getMessage() . "<br>";
}

// Test 4: Login Test
echo "<h2>Test 4: Login Test</h2>";
$test_username = "admin";
$test_password = "admin123"; // Default admin password

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $test_username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ User found: " . $row['username'] . "<br>";
        
        if (verifyPassword($test_password, $row['password_hash'])) {
            echo "✅ Password verification successful<br>";
            
            // Set session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            
            echo "✅ Session data set<br>";
            echo "Session data after login: " . json_encode($_SESSION) . "<br>";
            echo "Session ID: " . session_id() . "<br>";
        } else {
            echo "❌ Password verification failed<br>";
            echo "Note: Default password might be different. Check database.<br>";
        }
    } else {
        echo "❌ User not found: " . $test_username . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Login test error: " . $e->getMessage() . "<br>";
}

// Test 5: Logout Test
echo "<h2>Test 5: Logout Test</h2>";
echo "Session before logout: " . json_encode($_SESSION) . "<br>";
echo "Cookie before logout: " . json_encode($_COOKIE) . "<br>";

// Unset session
$_SESSION = array();

// Delete cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
    unset($_COOKIE[session_name()]);
    echo "✅ Session cookie deleted<br>";
}

// Destroy session
session_destroy();

echo "Session after logout: " . json_encode($_SESSION) . "<br>";
echo "Cookie after logout: " . json_encode($_COOKIE) . "<br>";
echo "✅ Logout completed<br>";

// Test 6: API Test
echo "<h2>Test 6: API Endpoint Test</h2>";
echo "<a href='api/auth.php?action=check_session' target='_blank'>Test check_session API</a><br>";
echo "<a href='index.html' target='_blank'>Go to main application</a><br>";

// Test 7: List Users
echo "<h2>Test 7: List Users in Database</h2>";
try {
    $stmt = $db->query("SELECT id, username, full_name, role FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error listing users: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p>Check the results above to identify the issue.</p>";
?>
