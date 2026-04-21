<?php
// Install PHPMailer
// Download và cài đặt PHPMailer library

echo "<h1>📦 Install PHPMailer</h1>";

// Create vendor directory if not exists
$vendor_dir = __DIR__ . '/vendor';
if (!is_dir($vendor_dir)) {
    mkdir($vendor_dir, 0755, true);
    echo "<p>✅ <strong>Created vendor directory:</strong> {$vendor_dir}</p>";
} else {
    echo "<p>⚠️ <strong>Vendor directory already exists</strong></p>";
}

// Create PHPMailer directory
$phpmailer_dir = $vendor_dir . '/phpmailer';
if (!is_dir($phpmailer_dir)) {
    mkdir($phpmailer_dir, 0755, true);
    echo "<p>✅ <strong>Created PHPMailer directory:</strong> {$phpmailer_dir}</p>";
}

// Download PHPMailer (using curl)
$phpmailer_url = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';
$zip_file = __DIR__ . '/phpmailer.zip';

echo "<h2>Step 1: Download PHPMailer</h2>";
echo "<p>🔄 <strong>Downloading from:</strong> {$phpmailer_url}</p>";

// Download using curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $phpmailer_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$zip_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($zip_content)) {
    file_put_contents($zip_file, $zip_content);
    echo "<p>✅ <strong>Downloaded PHPMailer:</strong> {$zip_file}</p>";
    
    // Extract ZIP
    echo "<h2>Step 2: Extract PHPMailer</h2>";
    
    $zip = new ZipArchive();
    if ($zip->open($zip_file) === TRUE) {
        $zip->extractTo($vendor_dir);
        $zip->close();
        echo "<p>✅ <strong>Extracted PHPMailer to:</strong> {$vendor_dir}</p>";
        
        // Move files to correct location
        $extracted_dir = $vendor_dir . '/PHPMailer-6.9.1';
        if (is_dir($extracted_dir)) {
            $src_dir = $extracted_dir . '/src';
            if (is_dir($src_dir)) {
                // Copy src to phpmailer directory
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($src_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $item) {
                    $dest = $phpmailer_dir . '/' . $iterator->getSubPathName();
                    if ($item->isDir()) {
                        mkdir($dest, 0755, true);
                    } else {
                        copy($item, $dest);
                    }
                }
                echo "<p>✅ <strong>Copied PHPMailer files to:</strong> {$phpmailer_dir}</p>";
            }
        }
        
        // Clean up
        unlink($zip_file);
        $this->removeDirectory($extracted_dir);
        echo "<p>✅ <strong>Cleaned up temporary files</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Failed to extract ZIP file</strong></p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ <strong>Failed to download PHPMailer</strong> (HTTP {$http_code})</p>";
    echo "<p><strong>Alternative:</strong> Download manually from <a href='{$phpmailer_url}' target='_blank'>GitHub</a></p>";
}

echo "<hr>";
echo "<h2>Step 3: Create Autoloader</h2>";

$autoloader_content = '<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    $prefix = "PHPMailer\\PHPMailer\\";
    $base_dir = __DIR__ . "/vendor/phpmailer/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});
?>';

file_put_contents(__DIR__ . '/autoloader.php', $autoloader_content);
echo "<p>✅ <strong>Created autoloader:</strong> autoloader.php</p>";

echo "<hr>";
echo "<h2>Step 4: Update EmailHelper</h2>";

$emailhelper_content = file_get_contents(__DIR__ . '/lib/EmailHelper.php');

// Add autoloader include at the top
if (strpos($emailhelper_content, 'require_once __DIR__ . \'/../autoloader.php\';') === false) {
    $emailhelper_content = str_replace('<?php', '<?php
require_once __DIR__ . \'/../autoloader.php\';', $emailhelper_content);
    file_put_contents(__DIR__ . '/lib/EmailHelper.php', $emailhelper_content);
    echo "<p>✅ <strong>Updated EmailHelper with autoloader</strong></p>";
} else {
    echo "<p>⚠️ <strong>EmailHelper already has autoloader</strong></p>";
}

echo "<hr>";
echo "<h2>✅ Installation Complete</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>PHPMailer đã được cài đặt thành công!</strong></p>";
echo "<p><strong>Files created:</strong></p>";
echo "<ul>";
echo "<li>vendor/phpmailer/ - PHPMailer library</li>";
echo "<li>autoloader.php - Autoloader</li>";
echo "<li>lib/EmailHelper.php - Updated with autoloader</li>";
echo "</ul>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test lại: <code>php test-send-email.php</code></li>";
echo "<li>Kiểm tra email flow: <code>php test-full-email-flow.php</code></li>";
echo "<li>Xác nhận admin nhận email</li>";
echo "</ol>";
echo "</div>";

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
?>
