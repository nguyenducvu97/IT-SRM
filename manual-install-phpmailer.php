<?php
// Manual PHPMailer Installation
// Cài đặt PHPMailer thủ công khi ZipArchive không available

echo "<h1>🔧 Manual PHPMailer Installation</h1>";

// Step 1: Download individual PHPMailer files
$files_to_download = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/Exception.php'
];

echo "<h2>Step 1: Create Directories</h2>";

// Create vendor directory
$vendor_dir = __DIR__ . '/vendor';
if (!is_dir($vendor_dir)) {
    mkdir($vendor_dir, 0755, true);
    echo "<p>✅ <strong>Created vendor directory</strong></p>";
}

// Create PHPMailer directory
$phpmailer_dir = $vendor_dir . '/phpmailer';
if (!is_dir($phpmailer_dir)) {
    mkdir($phpmailer_dir, 0755, true);
    echo "<p>✅ <strong>Created PHPMailer directory</strong></p>";
}

echo "<h2>Step 2: Download PHPMailer Files</h2>";

$downloaded_count = 0;
foreach ($files_to_download as $filename => $url) {
    echo "<p>🔄 <strong>Downloading:</strong> {$filename}</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($content)) {
        file_put_contents($phpmailer_dir . '/' . $filename, $content);
        echo "<p>✅ <strong>Downloaded:</strong> {$filename}</p>";
        $downloaded_count++;
    } else {
        echo "<p style='color: red;'>❌ <strong>Failed:</strong> {$filename} (HTTP {$http_code})</p>";
    }
}

echo "<h2>Step 3: Create Simple Autoloader</h2>";

$autoloader_content = '<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    // Map PHPMailer classes
    $class_map = [
        "PHPMailer\\PHPMailer\\PHPMailer" => __DIR__ . "/vendor/phpmailer/PHPMailer.php",
        "PHPMailer\\PHPMailer\\SMTP" => __DIR__ . "/vendor/phpmailer/SMTP.php",
        "PHPMailer\\PHPMailer\\Exception" => __DIR__ . "/vendor/phpmailer/Exception.php"
    ];
    
    if (isset($class_map[$class])) {
        require_once $class_map[$class];
    }
});
?>';

file_put_contents(__DIR__ . '/autoloader.php', $autoloader_content);
echo "<p>✅ <strong>Created autoloader:</strong> autoloader.php</p>";

echo "<h2>Step 4: Update EmailHelper</h2>";

$emailhelper_path = __DIR__ . '/lib/EmailHelper.php';
$emailhelper_content = file_get_contents($emailhelper_path);

// Add autoloader include
$autoloader_include = '<?php
require_once __DIR__ . \'/../autoloader.php\';';

if (strpos($emailhelper_content, 'require_once __DIR__ . \'/../autoloader.php\';') === false) {
    $emailhelper_content = str_replace('<?php', $autoloader_include, $emailhelper_content);
    file_put_contents($emailhelper_path, $emailhelper_content);
    echo "<p>✅ <strong>Updated EmailHelper with autoloader</strong></p>";
} else {
    echo "<p>⚠️ <strong>EmailHelper already has autoloader</strong></p>";
}

echo "<hr>";
echo "<h2>✅ Installation Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Files Downloaded:</strong> {$downloaded_count}/3</p>";
echo "<p><strong>PHPMailer Status:</strong> " . ($downloaded_count === 3 ? '✅ Complete' : '⚠️ Partial') . "</p>";

if ($downloaded_count === 3) {
    echo "<p><strong>🎉 PHPMailer is ready!</strong></p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Test email:</strong> <code>php test-send-email.php</code></li>";
    echo "<li><strong>Test flow:</strong> <code>php test-full-email-flow.php</code></li>";
    echo "<li><strong>Check admin email:</strong> ndvu@sgitech.com.vn</li>";
    echo "</ol>";
} else {
    echo "<p><strong>⚠️ Some files failed to download</strong></p>";
    echo "<p><strong>Manual download required:</strong></p>";
    echo "<ul>";
    foreach ($files_to_download as $filename => $url) {
        echo "<li><a href='{$url}' target='_blank'>{$filename}</a></li>";
    }
    echo "</ul>";
}

echo "</div>";

// Test if PHPMailer is available
echo "<h2>Step 5: Test PHPMailer</h2>";

try {
    require_once __DIR__ . '/autoloader.php';
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color: green;'>✅ <strong>PHPMailer class is available!</strong></p>";
        
        // Test instantiation
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        echo "<p style='color: green;'>✅ <strong>PHPMailer instantiation successful!</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>PHPMailer class not found</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>PHPMailer test failed:</strong> " . $e->getMessage() . "</p>";
}
?>
