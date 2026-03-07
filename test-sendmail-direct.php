<?php
// Test sendmail.exe directly to see the exact error
echo "<h2>🔧 Testing sendmail.exe Directly</h2>";

echo "<h3>🔍 Diagnosing sendmail.exe Issues:</h3>";

$sendmail_exe = 'C:\xampp\sendmail\sendmail.exe';
$test_email = 'ndvu@sgitech.com.vn';
$test_subject = '🔧 SENDMAIL DIRECT TEST';
$test_content = "From: ndvu@sgitech.com.vn\nTo: $test_email\nSubject: $test_subject\n\nThis is a direct test of sendmail.exe.";

echo "<h4>📧 Testing sendmail.exe Command:</h4>";
echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "Command: echo \"$test_content\" | \"$sendmail_exe\" -t<br>";
echo "</div>";

// Create temporary test file
$temp_file = tempnam(sys_get_temp_dir(), 'sendmail_test');
file_put_contents($temp_file, $test_content);

echo "<h4>🧪 Executing sendmail.exe:</h4>";

// Execute sendmail.exe and capture output
$command = "\"$sendmail_exe\" -t < \"$temp_file\"";
$output = [];
$return_code = 0;

exec($command, $output, $return_code);

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h5>Command Output:</h5>";
if (!empty($output)) {
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "No output from sendmail.exe<br>";
}
echo "<h5>Return Code:</h5> $return_code";
echo "</div>";

// Clean up
unlink($temp_file);

// Analyze return code
echo "<h4>📊 Return Code Analysis:</h4>";
if ($return_code === 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h5>✅ sendmail.exe executed successfully!</h5>";
    echo "<p>If email still doesn't arrive, the issue is with SMTP server communication.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h5>❌ sendmail.exe failed with code: $return_code</h5>";
    
    switch($return_code) {
        case 1:
            echo "<p>General error - check sendmail.ini configuration</p>";
            break;
        case 2:
            echo "<p>Connection failed - cannot reach SMTP server</p>";
            break;
        case 3:
            echo "<p>Authentication failed - wrong credentials</p>";
            break;
        default:
            echo "<p>Unknown error - check sendmail debug logs</p>";
    }
    echo "</div>";
}

echo "<hr>";

echo "<h3>🔍 Checking sendmail Debug Logs:</h3>";

$debug_log = 'C:\xampp\sendmail\debug.log';
$error_log = 'C:\xampp\sendmail\error.log';

echo "<h4>📋 Debug Log:</h4>";
if (file_exists($debug_log)) {
    $debug_content = file_get_contents($debug_log);
    if (!empty($debug_content)) {
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
        echo nl2br(htmlspecialchars($debug_content));
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>Debug log is empty</p>";
    }
} else {
    echo "<p style='color: red;'>Debug log not found</p>";
}

echo "<h4>📋 Error Log:</h4>";
if (file_exists($error_log)) {
    $error_content = file_get_contents($error_log);
    if (!empty($error_content)) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
        echo nl2br(htmlspecialchars($error_content));
        echo "</div>";
    } else {
        echo "<p style='color: green;'>No errors in error log</p>";
    }
} else {
    echo "<p style='color: red;'>Error log not found</p>";
}

echo "<hr>";

echo "<h3>💡 Quick Fix Options:</h3>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
echo "<div>";
echo "<h4>🔧 Option 1: Fix sendmail.exe</h4>";
echo "<ol>";
echo "<li>Check sendmail debug logs above</li>";
echo "<li>Verify SMTP server accessibility</li>";
echo "<li>Test credentials manually</li>";
echo "<li>Check firewall permissions</li>";
echo "</ol>";
echo "</div>";
echo "<div>";
echo "<h4>🌐 Option 2: Use Gmail SMTP</h4>";
echo "<ol>";
echo "<li>Create Gmail App Password</li>";
echo "<li>Configure PHPMailer for Gmail</li>";
echo "<li>Test with external service</li>";
echo "<li>Guaranteed delivery</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "<hr>";

echo "<h3>🎯 Recommendation:</h3>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
echo "<h4>💡 Suggested Solution:</h4>";
echo "<p>Based on the analysis, the internal mail system has fundamental issues. The fastest and most reliable solution is to configure Gmail SMTP:</p>";
echo "<ul>";
echo "<li>✅ Guaranteed to work</li>";
echo "<li>✅ No internal server dependencies</li>";
echo "<li>✅ Easy to configure</li>";
echo "<li>✅ Reliable delivery</li>";
echo "</ul>";
echo "<p><strong>Would you like me to configure Gmail SMTP as a backup solution?</strong></p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
