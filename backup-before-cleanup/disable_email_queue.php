<?php
// Email Queue Control Script
// This script helps you easily enable/disable email queue processing

$config_file = __DIR__ . '/config/email_queue_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'disable') {
        $config_content = '<?php
// Email Queue Configuration
// Set to false to disable automatic background processing
define(\'ENABLE_EMAIL_QUEUE_PROCESSING\', false);

// Set to true to enable automatic background processing  
// define(\'ENABLE_EMAIL_QUEUE_PROCESSING\', true);

// Log file for debugging
define(\'EMAIL_QUEUE_LOG\', __DIR__ . \'/../logs/email_queue_debug.log\');
?>';
        
        file_put_contents($config_file, $config_content);
        $message = "✅ Email queue processing has been DISABLED";
        $status = "disabled";
        
    } elseif ($action === 'enable') {
        $config_content = '<?php
// Email Queue Configuration
// Set to false to disable automatic background processing
// define(\'ENABLE_EMAIL_QUEUE_PROCESSING\', false);

// Set to true to enable automatic background processing  
define(\'ENABLE_EMAIL_QUEUE_PROCESSING\', true);

// Log file for debugging
define(\'EMAIL_QUEUE_LOG\', __DIR__ . \'/../logs/email_queue_debug.log\');
?>';
        
        file_put_contents($config_file, $config_content);
        $message = "✅ Email queue processing has been ENABLED";
        $status = "enabled";
    }
}

// Check current status
$current_status = "unknown";
if (file_exists($config_file)) {
    $content = file_get_contents($config_file);
    if (strpos($content, 'ENABLE_EMAIL_QUEUE_PROCESSING\', true') !== false) {
        $current_status = "enabled";
    } elseif (strpos($content, 'ENABLE_EMAIL_QUEUE_PROCESSING\', false') !== false) {
        $current_status = "disabled";
    }
}

$status_color = [
    'enabled' => '#28a745',
    'disabled' => '#dc3545',
    'unknown' => '#6c757d'
];

$status_icon = [
    'enabled' => '🟢',
    'disabled' => '🔴', 
    'unknown' => '⚪'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Queue Control</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            background: <?php echo $status_color[$current_status]; ?>;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .enable-btn {
            background-color: #28a745;
            color: white;
        }
        .enable-btn:hover {
            background-color: #218838;
        }
        .disable-btn {
            background-color: #dc3545;
            color: white;
        }
        .disable-btn:hover {
            background-color: #c82333;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .explanation {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            margin-top: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Queue Control</h1>
        
        <div class="status">
            <?php echo $status_icon[$current_status]; ?> 
            Current Status: <?php echo strtoupper($current_status); ?>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="buttons">
                <button type="submit" name="action" value="enable" class="enable-btn">
                    ✅ ENABLE Email Queue
                </button>
                <button type="submit" name="action" value="disable" class="disable-btn">
                    ❌ DISABLE Email Queue
                </button>
            </div>
        </form>
        
        <div class="explanation">
            <h3>📖 Giải thích:</h3>
            <p><strong>ENABLE:</strong> Email queue sẽ tự động xử lý trong nền (background). File process_email_queue.php sẽ tự động chạy khi có email mới.</p>
            <p><strong>DISABLE:</strong> Email queue sẽ không tự động xử lý. File process_email_queue.php sẽ KHÔNG tự động mở. Email vẫn được lưu trong queue nhưng không gửi đi.</p>
            <br>
            <p><strong>⚠️ Lưu ý:</strong> Khi DISABLE, bạn cần xử lý email queue thủ công nếu cần gửi email.</p>
        </div>
    </div>
</body>
</html>
