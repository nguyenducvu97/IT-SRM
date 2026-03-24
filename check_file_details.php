<?php
$filePath = 'uploads/completed/69c2432932d05_Picture1.png';

echo '<h1>File Analysis: 69c2432932d05_Picture1.png</h1>';

echo '<p><strong>File path:</strong> ' . $filePath . '</p>';
echo '<p><strong>File exists:</strong> ' . (file_exists($filePath) ? 'YES' : 'NO') . '</p>';
echo '<p><strong>File size:</strong> ' . filesize($filePath) . ' bytes</p>';

// Check MIME type
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $filePath);
finfo_close($fileInfo);
echo '<p><strong>Detected MIME type:</strong> ' . $mimeType . '</p>';

// Check file extension
$extension = pathinfo($filePath, PATHINFO_EXTENSION);
echo '<p><strong>File extension:</strong> ' . $extension . '</p>';

// Read first few bytes to check file signature
$handle = fopen($filePath, 'rb');
$firstBytes = fread($handle, 8);
fclose($handle);

echo '<p><strong>First 8 bytes (hex):</strong> ' . bin2hex($firstBytes) . '</p>';
echo '<p><strong>First 8 bytes (ASCII):</strong> ' . $firstBytes . '</p>';

// PNG signature should be: 89 50 4E 47 0D 0A 1A 0A
$pngSignature = hex2bin('89504e470d0a1a0a');
if (substr($firstBytes, 0, 8) === $pngSignature) {
    echo '<p style="color: green;"><strong>✅ Valid PNG signature detected</strong></p>';
} else {
    echo '<p style="color: red;"><strong>❌ Invalid PNG signature</strong></p>';
}

// Try to display image info
if ($imageInfo = @getimagesize($filePath)) {
    echo '<p><strong>Image dimensions:</strong> ' . $imageInfo[0] . 'x' . $imageInfo[1] . '</p>';
    echo '<p><strong>Image type:</strong> ' . $imageInfo[2] . '</p>';
    echo '<p><strong>Image MIME:</strong> ' . $imageInfo['mime'] . '</p>';
} else {
    echo '<p style="color: red;"><strong>❌ Not a valid image file</strong></p>';
}

// Display the image
echo '<h2>Image Display Test:</h2>';
echo '<img src="' . $filePath . '" alt="Test Image" style="max-width: 200px; border: 1px solid #ccc;" onerror="this.style.background=\'red\'">';
?>
