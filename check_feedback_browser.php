<!DOCTYPE html>
<html>
<head>
    <title>Check Feedback Data</title>
</head>
<body>
    <h1>Check Feedback Data</h1>
    <?php
    require_once 'config/database.php';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo '<h2>Request #8 Feedback Data:</h2>';
        
        // Check service_requests table for feedback columns
        $stmt = $pdo->prepare('SELECT id, status, feedback_rating, feedback_text, feedback_created_at FROM service_requests WHERE id = ?');
        $stmt->execute([8]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            echo '<p><strong>Request found:</strong></p>';
            echo '<ul>';
            echo '<li>ID: ' . htmlspecialchars($request['id']) . '</li>';
            echo '<li>Status: ' . htmlspecialchars($request['status']) . '</li>';
            echo '<li>Feedback Rating: ' . htmlspecialchars($request['feedback_rating'] ?? 'NULL') . '</li>';
            echo '<li>Feedback Text: ' . htmlspecialchars($request['feedback_text'] ?? 'NULL') . '</li>';
            echo '<li>Feedback Created At: ' . htmlspecialchars($request['feedback_created_at'] ?? 'NULL') . '</li>';
            echo '</ul>';
            
            if ($request['feedback_rating'] || $request['feedback_text']) {
                echo '<p style="color: green;">✅ This request HAS feedback data - Admin should see it!</p>';
            } else {
                echo '<p style="color: red;">❌ This request has NO feedback data</p>';
            }
        } else {
            echo '<p style="color: red;">❌ Request #8 not found</p>';
        }
        
        echo '<h2>All Requests With Feedback:</h2>';
        
        // Check all requests with feedback data
        $stmt = $pdo->prepare('SELECT id, title, status, feedback_rating, feedback_text FROM service_requests WHERE feedback_rating IS NOT NULL OR feedback_text IS NOT NULL');
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($requests) > 0) {
            echo '<p>Found ' . count($requests) . ' requests with feedback:</p>';
            echo '<ul>';
            foreach ($requests as $req) {
                echo '<li>';
                echo '<strong>Request #' . htmlspecialchars($req['id']) . '</strong> - ' . htmlspecialchars($req['title']) . ' (Status: ' . htmlspecialchars($req['status']) . ')';
                echo '<br>Rating: ' . htmlspecialchars($req['feedback_rating'] ?? 'NULL');
                echo '<br>Text: ' . htmlspecialchars(substr($req['feedback_text'] ?? '', 0, 50)) . '...';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p style="color: red;">❌ No requests found with feedback data</p>';
        }
        
    } catch (Exception $e) {
        echo '<p style="color: red;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
</body>
</html>
