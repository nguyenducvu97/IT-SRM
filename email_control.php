<?php
// Email Processing Control Panel
// Quản lý chế độ xử lý email

$config_file = __DIR__ . '/config/email_queue_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'background';
    
    $config_content = '<?php
// Email Queue Configuration
// Email processing mode: \'inline\', \'background\', \'disabled\'

// \'inline\' - Gửi email ngay lập tức (chậm cho user)
// \'background\' - Lưu vào queue và xử lý ngầm (nhanh cho user) 
// \'disabled\' - Không gửi email (chỉ lưu để debug)
define(\'EMAIL_PROCESSING_MODE\', \'' . $mode . '\');

// Background processing settings
define(\'BACKGROUND_PROCESSING_METHOD\', \'exec\'); // \'exec\', \'curl\', \'popen\'
define(\'BACKGROUND_TIMEOUT\', 1); // seconds
define(\'ENABLE_BACKGROUND_LOGGING\', true);

// Log file for debugging
define(\'EMAIL_QUEUE_LOG\', __DIR__ . \'/../logs/email_queue_debug.log\');

// Helper function to check if background processing is enabled
function isBackgroundProcessingEnabled() {
    return defined(\'EMAIL_PROCESSING_MODE\') && EMAIL_PROCESSING_MODE === \'background\';
}

// Helper function to check if email processing is disabled
function isEmailProcessingDisabled() {
    return defined(\'EMAIL_PROCESSING_MODE\') && EMAIL_PROCESSING_MODE === \'disabled\';
}
?>';
    
    file_put_contents($config_file, $config_content);
    
    $messages = [
        'background' => '✅ Email processing set to BACKGROUND - Emails will be sent in background without opening files',
        'inline' => '🔄 Email processing set to INLINE - Emails will be sent immediately (slower for users)',
        'disabled' => '❌ Email processing set to DISABLED - Emails will be queued but not sent'
    ];
    
    $message = $messages[$mode] ?? 'Configuration updated';
}

// Check current mode
$current_mode = 'unknown';
if (file_exists($config_file)) {
    $content = file_get_contents($config_file);
    if (strpos($content, 'EMAIL_PROCESSING_MODE\', \'background\'') !== false) {
        $current_mode = 'background';
    } elseif (strpos($content, 'EMAIL_PROCESSING_MODE\', \'inline\'') !== false) {
        $current_mode = 'inline';
    } elseif (strpos($content, 'EMAIL_PROCESSING_MODE\', \'disabled\'') !== false) {
        $current_mode = 'disabled';
    }
}

$mode_info = [
    'background' => [
        'color' => '#28a745',
        'icon' => '🚀',
        'title' => 'BACKGROUND MODE',
        'description' => 'Email được lưu vào queue và xử lý ngầm. User nhận response nhanh. File không tự động mở.',
        'pros' => ['⚡ Nhanh cho user', '🔄 Xử lý ngầm', '📁 Không mở file mới'],
        'cons' => ['⏳ Email có độ trễ nhỏ', '📝 Cần kiểm tra queue']
    ],
    'inline' => [
        'color' => '#007bff',
        'icon' => '⚡',
        'title' => 'INLINE MODE',
        'description' => 'Email được gửi ngay lập tức. User phải chờ email gửi xong.',
        'pros' => ['📧 Email ngay lập tức', '✅ Đảm bảo gửi', '🔍 Dễ debug'],
        'cons' => ['🐌 Chậm cho user', '⏱️ Timeout có thể xảy ra', '🚫 Block user response']
    ],
    'disabled' => [
        'color' => '#dc3545',
        'icon' => '🚫',
        'title' => 'DISABLED MODE',
        'description' => 'Email không được gửi. Chỉ lưu trong queue để debug.',
        'pros' => ['🔧 Dễ debug', '⚡ Nhanh nhất', '📝 Không lỗi email'],
        'cons' => ['❌ Không gửi email', '📧 Queue đầy lên', '👥 User không nhận email']
    ],
    'unknown' => [
        'color' => '#6c757d',
        'icon' => '❓',
        'title' => 'UNKNOWN MODE',
        'description' => 'Không xác định được chế độ hiện tại.',
        'pros' => [],
        'cons' => []
    ]
];

$info = $mode_info[$current_mode];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Processing Control</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
            background: <?php echo $info['color']; ?>;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        .status .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .modes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .mode-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .mode-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .mode-card.active {
            border-color: <?php echo $info['color']; ?>;
            background-color: <?php echo $info['color']; ?>20;
        }
        .mode-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: <?php echo $info['color']; ?>;
        }
        .mode-description {
            font-size: 14px;
            margin-bottom: 10px;
            color: #666;
        }
        .pros-cons {
            font-size: 12px;
        }
        .pros {
            color: #28a745;
            margin-bottom: 5px;
        }
        .cons {
            color: #dc3545;
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
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-background { background-color: #28a745; color: white; }
        .btn-background:hover { background-color: #218838; }
        .btn-inline { background-color: #007bff; color: white; }
        .btn-inline:hover { background-color: #0056b3; }
        .btn-disabled { background-color: #dc3545; color: white; }
        .btn-disabled:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Processing Control</h1>
        
        <div class="status">
            <span class="icon"><?php echo $info['icon']; ?></span>
            <?php echo $info['title']; ?>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="modes">
            <div class="mode-card <?php echo $current_mode === 'background' ? 'active' : ''; ?>">
                <div class="mode-title">🚀 BACKGROUND MODE</div>
                <div class="mode-description">
                    Email được xử lý ngầm, user không phải chờ
                </div>
                <div class="pros-cons">
                    <div class="pros">✅ Nhanh cho user</div>
                    <div class="pros">✅ Không mở file</div>
                    <div class="pros">✅ Xử lý ngầm</div>
                </div>
            </div>
            
            <div class="mode-card <?php echo $current_mode === 'inline' ? 'active' : ''; ?>">
                <div class="mode-title">⚡ INLINE MODE</div>
                <div class="mode-description">
                    Email được gửi ngay lập tức
                </div>
                <div class="pros-cons">
                    <div class="pros">✅ Gửi ngay</div>
                    <div class="pros">✅ Đảm bảo</div>
                    <div class="cons">❌ Chậm cho user</div>
                </div>
            </div>
            
            <div class="mode-card <?php echo $current_mode === 'disabled' ? 'active' : ''; ?>">
                <div class="mode-title">🚫 DISABLED MODE</div>
                <div class="mode-description">
                    Email không được gửi, chỉ để debug
                </div>
                <div class="pros-cons">
                    <div class="pros">✅ Dễ debug</div>
                    <div class="pros">✅ Nhanh nhất</div>
                    <div class="cons">❌ Không gửi email</div>
                </div>
            </div>
        </div>
        
        <form method="POST">
            <div class="form-section">
                <h3>Chọn chế độ xử lý email:</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px;">
                    <button type="submit" name="mode" value="background" class="btn-background">
                        🚀 BACKGROUND MODE
                    </button>
                    <button type="submit" name="mode" value="inline" class="btn-inline">
                        ⚡ INLINE MODE
                    </button>
                    <button type="submit" name="mode" value="disabled" class="btn-disabled">
                        🚫 DISABLED MODE
                    </button>
                </div>
            </div>
        </form>
        
        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>📖 Giải thích chi tiết:</h4>
            <p><strong>🚀 BACKGROUND MODE (Khuyến nghị):</strong> Email được lưu vào queue và xử lý ngầm. User tạo yêu cầu nhận response ngay lập tức. Email được gửi trong nền mà không cần mở file mới.</p>
            <p><strong>⚡ INLINE MODE:</strong> Email được gửi ngay khi user tạo yêu cầu. User phải chờ cho đến khi email gửi xong. Có thể gây timeout nếu email server chậm.</p>
            <p><strong>🚫 DISABLED MODE:</strong> Email không được gửi. Chỉ lưu trong queue để kiểm tra. Dùng khi debug hoặc test.</p>
        </div>
    </div>
</body>
</html>
