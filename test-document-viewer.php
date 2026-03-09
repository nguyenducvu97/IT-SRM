<?php
// Test document viewer functionality
echo "<h2>🧪 Testing Document Viewer Functionality</h2>";

echo "<h3>🔧 Features Added:</h3>";
echo "<ul>";
echo "<li>✅ <strong>View Button:</strong> Added for document files (PDF, Word, Excel, PowerPoint, Text)</li>";
echo "<li>✅ <strong>File Type Detection:</strong> Automatic icon based on file extension</li>";
echo "<li>✅ <strong>PDF Viewer:</strong> Inline iframe display</li>";
echo "<li>✅ <strong>Document Info:</strong> Shows appropriate viewer for each file type</li>";
echo "<li>✅ <strong>Text Files:</strong> Loads and displays content directly</li>";
echo "<li>✅ <strong>Responsive Design:</strong> Works on mobile devices</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>📋 Supported File Types:</h3>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;'>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4><i class='fas fa-file-pdf' style='color: #dc3545;'></i> PDF Files</h4>";
echo "<p>• Inline iframe viewer<br>• New tab option<br>• Download fallback</p>";
echo "</div>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #2b579a;'>";
echo "<h4><i class='fas fa-file-word' style='color: #2b579a;'></i> Word Documents</h4>";
echo "<p>• .doc, .docx support<br>• Open in Word Online<br>• Google Docs option</p>";
echo "</div>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #217346;'>";
echo "<h4><i class='fas fa-file-excel' style='color: #217346;'></i> Excel Spreadsheets</h4>";
echo "<p>• .xls, .xlsx support<br>• Open in Excel Online<br>• Google Sheets option</p>";
echo "</div>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #d24726;'>";
echo "<h4><i class='fas fa-file-powerpoint' style='color: #d24726;'></i> PowerPoint</h4>";
echo "<p>• .ppt, .pptx support<br>• Open in PowerPoint Online<br>• Google Slides option</p>";
echo "</div>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #6c757d;'>";
echo "<h4><i class='fas fa-file-alt' style='color: #6c757d;'></i> Text Files</h4>";
echo "<p>• .txt, .md support<br>• Direct content display<br>• Syntax highlighting</p>";
echo "</div>";
echo "</div>";

echo "<hr>";

echo "<h3>🧪 Test the Document Viewer:</h3>";

// Check if we have any test files
$upload_dir = "uploads/requests/";
$test_files = [];

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $upload_dir . $file;
            if (file_exists($file_path)) {
                $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $test_files[] = [
                    'filename' => $file,
                    'path' => $file_path,
                    'ext' => $file_ext,
                    'size' => filesize($file_path)
                ];
            }
        }
    }
}

if (!empty($test_files)) {
    echo "<h4>📎 Available Test Files:</h4>";
    echo "<div style='background: white; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;'>";
    echo "<div class='attachments-list'>";
    
    foreach ($test_files as $file) {
        $isImage = in_array($file['ext'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $isPDF = $file['ext'] === 'pdf';
        $isWord = in_array($file['ext'], ['doc', 'docx']);
        $isExcel = in_array($file['ext'], ['xls', 'xlsx']);
        $isPowerPoint = in_array($file['ext'], ['ppt', 'pptx']);
        $isText = in_array($file['ext'], ['txt', 'md']);
        $isViewable = $isPDF || $isWord || $isExcel || $isPowerPoint || $isText;
        
        echo "<div class='attachment-item' style='display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 10px;'>";
        echo "<div class='attachment-info' style='display: flex; align-items: center; gap: 10px;'>";
        echo "<i class='fas fa-" . ($isImage ? 'image' : $isPDF ? 'file-pdf' : $isWord ? 'file-word' : $isExcel ? 'file-excel' : $isPowerPoint ? 'file-powerpoint' : 'file') . "' style='color: " . ($isPDF ? '#dc3545' : $isWord ? '#2b579a' : $isExcel ? '#217346' : $isPowerPoint ? '#d24726' : '#6c757d') . "; font-size: 1.2rem;'></i>";
        echo "<div>";
        echo "<div class='attachment-name' style='font-weight: 500; color: #495057;'>" . htmlspecialchars($file['filename']) . "</div>";
        echo "<div class='attachment-size' style='font-size: 0.85rem; color: #6c757d;'>" . number_format($file['size']) . " bytes</div>";
        echo "</div>";
        echo "</div>";
        echo "<div class='attachment-actions' style='display: flex; gap: 8px;'>";
        
        if ($isImage) {
            echo "<img src='" . $file['path'] . "' alt='" . htmlspecialchars($file['filename']) . "' style='max-width: 60px; max-height: 40px; border-radius: 4px; cursor: pointer;' onclick='showImageModal(\"" . $file['path'] . "\", \"" . htmlspecialchars($file['filename']) . "\")'>";
        }
        
        if ($isViewable) {
            echo "<button class='btn btn-sm btn-primary' onclick='viewDocument(\"" . $file['path'] . "\", \"" . htmlspecialchars($file['filename']) . "\", \"" . $file['ext'] . "\")' style='padding: 6px 12px; font-size: 0.85rem; border: none; border-radius: 4px; background: #007bff; color: white; cursor: pointer;'>";
            echo "<i class='fas fa-eye'></i> Xem";
            echo "</button>";
        }
        
        echo "<a href='" . $file['path'] . "' download='" . htmlspecialchars($file['filename']) . "' class='btn btn-sm btn-secondary' style='padding: 6px 12px; font-size: 0.85rem; text-decoration: none; border-radius: 4px; background: #6c757d; color: white;'>";
        echo "<i class='fas fa-download'></i> Tải về";
        echo "</a>";
        
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p style='color: orange;'>⚠️ No test files found in uploads/requests/ directory</p>";
}

echo "<hr>";

echo "<h3>🎯 How to Test:</h3>";
echo "<ol>";
echo "<li><strong>Click 'Xem' button:</strong> Opens document viewer modal</li>";
echo "<li><strong>PDF files:</strong> Should display inline in iframe</li>";
echo "<li><strong>Office documents:</strong> Shows info with open/download options</li>";
echo "<li><strong>Text files:</strong> Loads and displays content directly</li>";
echo "<li><strong>Images:</strong> Click image to open image modal</li>";
echo "<li><strong>Download:</strong> Always available for all file types</li>";
echo "</ol>";

echo "<hr>";

echo "<h3>🔗 Test with Real Request:</h3>";
echo "<p><a href='request-detail.html?id=27' target='_blank' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>";
echo "<i class='fas fa-external-link-alt'></i> Test with Request 27 (Has Attachment)";
echo "</a></p>";

echo "<hr>";
echo "<p><strong>🎉 Document viewer functionality has been added!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>

<!-- Include the necessary JavaScript and CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">

<script>
// Mock functions for testing
function viewDocument(filePath, fileName, fileExt) {
    alert('Document viewer would open:\\n\\nFile: ' + fileName + '\\nType: ' + fileExt + '\\nPath: ' + filePath + '\\n\\nThis will work when loaded from the actual request detail page.');
}

function showImageModal(imageSrc, imageName) {
    alert('Image modal would open:\\n\\nImage: ' + imageName + '\\nPath: ' + imageSrc + '\\n\\nThis will work when loaded from the actual request detail page.');
}
</script>

<style>
.btn {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

.attachment-item {
    transition: all 0.2s ease;
}

.attachment-item:hover {
    background: #e9ecef !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
?>
