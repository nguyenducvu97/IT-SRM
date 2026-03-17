<?php
require_once 'config/session.php';
require_once 'config/database.php';

startSession();

// Simple login test
if ($_POST['action'] == 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            
            echo "<h2>✅ Login Success!</h2>";
            echo "<p>Session ID: " . session_id() . "</p>";
            echo "<p>User: " . $row['username'] . "</p>";
            echo "<p>Session Data: <pre>" . print_r($_SESSION, true) . "</pre></p>";
            echo "<p>Cookies: <pre>" . print_r($_COOKIE, true) . "</pre></p>";
            echo "<br><a href='test_login_simple.php'>Check Session</a>";
        } else {
            echo "<h2>❌ Invalid password</h2>";
        }
    } else {
        echo "<h2>❌ User not found</h2>";
    }
} else {
    // Check session
    if (isset($_SESSION['user_id'])) {
        echo "<h2>✅ Session Active!</h2>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>User: " . $_SESSION['username'] . "</p>";
        echo "<p>Session Data: <pre>" . print_r($_SESSION, true) . "</pre></p>";
        echo "<p>Cookies: <pre>" . print_r($_COOKIE, true) . "</pre></p>";
        echo "<br><a href='test_login_simple.php'>Refresh</a> | <a href='test_login_simple.php?logout=1'>Logout</a>";
    } else {
        ?>
        <h2>Simple Login Test</h2>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <p>Username: <input type="text" name="username" value="admin" required></p>
            <p>Password: <input type="password" name="password" value="admin" required></p>
            <p><input type="submit" value="Login"></p>
        </form>
        <?php
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: test_login_simple.php");
    exit();
}
?>
