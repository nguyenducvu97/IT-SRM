<?php
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

// Test login functionality
echo "<h2>Testing Login Functionality</h2>";

// Test 1: Check if admin user exists
echo "<h3>1. Checking if admin user exists:</h3>";
$db = getDatabaseConnection();
$stmt = $db->prepare("SELECT id, username, full_name, role FROM users WHERE username = 'admin'");
$stmt->execute();
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Admin user found: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</p>";
} else {
    echo "<p>❌ Admin user not found</p>";
}

// Test 2: Verify password hash
echo "<h3>2. Checking password hash:</h3>";
$stmt = $db->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
$stmt->execute();
$hash = $stmt->fetchColumn();
echo "<p>Password hash exists: " . ($hash ? "✅ Yes" : "❌ No") . "</p>";
echo "<p>Hash length: " . strlen($hash) . " characters</p>";

// Test 3: Test password verification
echo "<h3>3. Testing password verification:</h3>";
if ($hash && password_verify('admin123', $hash)) {
    echo "<p>✅ Password verification successful</p>";
} else {
    echo "<p>❌ Password verification failed</p>";
}

// Test 4: Test session functionality
echo "<h3>4. Testing session functionality:</h3>";
startSession();
$_SESSION['test'] = 'value';
echo "<p>Session test: " . (isset($_SESSION['test']) ? "✅ Working" : "❌ Not working") . "</p>";

// Test 5: Test API authentication
echo "<h3>5. Testing API authentication:</h3>";
// Simulate API login
$username = 'admin';
$password = 'admin123';

$query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (verifyPassword($password, $row['password_hash'])) {
        startSession();
        
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['role'] = $row['role'];
        
        echo "<p>✅ API login simulation successful</p>";
        echo "<p>User logged in: " . htmlspecialchars($_SESSION['username']) . " (" . htmlspecialchars($_SESSION['role']) . ")</p>";
        echo "<p>Session ID: " . session_id() . "</p>";
    } else {
        echo "<p>❌ Password verification failed in API test</p>";
    }
} else {
    echo "<p>❌ User not found in API test</p>";
}

// Test 6: Check if session is accessible
echo "<h3>6. Checking session persistence:</h3>";
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<p>✅ Session is active</p>";
    echo "<p>Current user: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</p>";
} else {
    echo "<p>❌ No active session</p>";
}
?>
