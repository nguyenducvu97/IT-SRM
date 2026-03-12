<?php
echo "Simple PHP test<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test session
session_start();
$_SESSION['test'] = 'Hello World';
echo "Session test: " . $_SESSION['test'] . "<br>";

// Test database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users count: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test file includes
echo "<br>Testing file includes:<br>";
if (file_exists('config/database.php')) {
    echo "✅ config/database.php exists<br>";
} else {
    echo "❌ config/database.php missing<br>";
}

if (file_exists('config/session.php')) {
    echo "✅ config/session.php exists<br>";
} else {
    echo "❌ config/session.php missing<br>";
}

if (file_exists('api/notifications.php')) {
    echo "✅ api/notifications.php exists<br>";
} else {
    echo "❌ api/notifications.php missing<br>";
}

if (file_exists('api/comments.php')) {
    echo "✅ api/comments.php exists<br>";
} else {
    echo "❌ api/comments.php missing<br>";
}
?>
