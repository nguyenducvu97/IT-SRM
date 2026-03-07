<?php
// Test SMTP connection and troubleshoot email delivery
echo "<h2>🔧 SMTP Connection and Email Troubleshooting</h2>";

echo "<h3>🌐 Testing Network Connection to gw.sgitech.com.vn</h3>";

// Test basic connectivity
$smtp_host = 'gw.sgitech.com.vn';
$ports_to_test = [25, 587, 465, 2525];

foreach ($ports_to_test as $port) {
    $timeout = 5;
    $connected = @fsockopen($smtp_host, $port, $errno, $errstr, $timeout);
    
    if ($connected) {
        fclose($connected);
        echo "<p style='color: green;'>✅ Port $port: Connected successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Port $port: Failed - $errstr ($errno)</p>";
    }
}

echo "<hr>";

echo "<h3>📧 Current PHPMailer Configuration:</h3>";
echo "<pre>";
echo "Host: gw.sgitech.com.vn
Port: 25
SMTPAuth: true
Username: ndvu@sgitech.com.vn
Password: ndvu
SMTPSecure: tls
CharSet: UTF-8
From: ndvu@sgitech.com.vn
</pre>";

echo "<hr>";

// Test PHPMailer with detailed error reporting
echo "<h3>🧪 Testing PHPMailer with Detailed Error Reporting:</h3>";

require_once 'lib/PHPMailerEmailHelper.php';

try {
    $mail = new PHPMailer();
    
    // Enable verbose debug output
    $mail->SMTPDebug = 2; // 0 = off, 1 = client messages, 2 = client and server messages
    $mail->Debugoutput = 'html';
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'gw.sgitech.com.vn';
    $mail->SMTPAuth = true;
    $mail->Username = 'ndvu@sgitech.com.vn';
    $mail->Password = 'ndvu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 25;
    
    // Recipients
    $mail->setFrom('ndvu@sgitech.com.vn', 'IT Service Request System');
    $mail->addAddress('ndvu@sgitech.com.vn', 'System Administrator');
    $mail->CharSet = 'UTF-8';
    
    // Content
    $mail->Subject = '🔧 SMTP Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h2>SMTP Connection Test</h2><p>This is a detailed test to troubleshoot SMTP connection issues.</p><p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    $mail->isHTML(true);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<h4>📤 SMTP Debug Output:</h4>";
    
    // Capture debug output
    ob_start();
    $result = $mail->send();
    $debug_output = ob_get_clean();
    
    echo $debug_output;
    echo "</div>";
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Email failed: " . $mail->ErrorInfo . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test alternative configurations
echo "<h3>🔄 Testing Alternative SMTP Configurations:</h3>";

$alternative_configs = [
    [
        'name' => 'Port 587 with TLS',
        'host' => 'gw.sgitech.com.vn',
        'port' => 587,
        'secure' => 'tls'
    ],
    [
        'name' => 'Port 25 without encryption',
        'host' => 'gw.sgitech.com.vn',
        'port' => 25,
        'secure' => ''
    ],
    [
        'name' => 'Port 465 with SSL',
        'host' => 'gw.sgitech.com.vn',
        'port' => 465,
        'secure' => 'ssl'
    ]
];

foreach ($alternative_configs as $config) {
    echo "<h4>🔧 Testing: {$config['name']}</h4>";
    
    try {
        $mail = new PHPMailer();
        $mail->SMTPDebug = 0; // Turn off debug for cleaner output
        
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = 'ndvu@sgitech.com.vn';
        $mail->Password = 'ndvu';
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        
        $mail->setFrom('ndvu@sgitech.com.vn', 'IT Service Request System');
        $mail->addAddress('ndvu@sgitech.com.vn', 'System Administrator');
        $mail->CharSet = 'UTF-8';
        
        $mail->Subject = "Test - {$config['name']} - " . date('H:i:s');
        $mail->Body = "<p>Test with {$config['name']}</p>";
        $mail->isHTML(true);
        
        if ($mail->send()) {
            echo "<p style='color: green;'>✅ {$config['name']}: Success</p>";
        } else {
            echo "<p style='color: red;'>❌ {$config['name']}: Failed - " . $mail->ErrorInfo . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ {$config['name']}: Exception - " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";

echo "<h3>🔍 Troubleshooting Checklist:</h3>";
echo "<ul>";
echo "<li>☐ Check if gw.sgitech.com.vn is accessible from this server</li>";
echo "<li>☐ Verify username/password are correct</li>";
echo "<li>☐ Check if SMTP server requires different encryption</li>";
echo "<li>☐ Verify firewall is not blocking SMTP ports</li>";
echo "<li>☐ Check if email server allows relaying from this IP</li>";
echo "<li>☐ Try testing with external email service (Gmail) as backup</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>💡 Recommended Solutions:</h3>";
echo "<ol>";
echo "<li><strong>If connection fails:</strong> Check network connectivity and firewall</li>";
echo "<li><strong>If authentication fails:</strong> Verify credentials with email admin</li>";
echo "<li><strong>If relaying denied:</strong> Check if server IP is allowed to send emails</li>";
echo "<li><strong>As backup:</strong> Configure external SMTP (Gmail, SendGrid)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='test-final-email-fix.php'>Test Email System</a></p>";
?>
