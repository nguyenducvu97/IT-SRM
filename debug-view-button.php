<?php
// Debug why view button is not showing
echo "<h2>🔍 Debug View Button Issue</h2>";

echo "<h3>🧪 Checking Current Request 27:</h3>";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get request 27 details with attachments
    $query = "SELECT r.*, a.id as attachment_id, a.filename, a.original_name, a.file_size, a.mime_type, a.uploaded_at
              FROM service_requests r
              LEFT JOIN attachments a ON r.id = a.service_request_id
              WHERE r.id = 27";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($results)) {
        $request = [
            'id' => $results[0]['id'],
            'title' => $results[0]['title'],
            'description' => $results[0]['description'],
            'status' => $results[0]['status'],
            'attachments' => []
        ];
        
        // Collect attachments
        foreach ($results as $row) {
            if ($row['attachment_id']) {
                $request['attachments'][] = [
                    'id' => $row['attachment_id'],
                    'filename' => $row['filename'],
                    'original_name' => $row['original_name'],
                    'file_size' => $row['file_size'],
                    'mime_type' => $row['mime_type'],
                    'uploaded_at' => $row['uploaded_at']
                ];
            }
        }
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<h4>📋 Request Details:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $request['id'] . "</li>";
        echo "<li><strong>Title:</strong> " . htmlspecialchars($request['title']) . "</li>";
        echo "<li><strong>Attachments:</strong> " . count($request['attachments']) . "</li>";
        echo "</ul>";
        echo "</div>";
        
        if (!empty($request['attachments'])) {
            echo "<h4>📎 Attachments Found:</h4>";
            
            foreach ($request['attachments'] as $attachment) {
                $fileExt = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
                $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                $isPDF = $fileExt === 'pdf';
                $isWord = in_array($fileExt, ['doc', 'docx']);
                $isExcel = in_array($fileExt, ['xls', 'xlsx']);
                $isPowerPoint = in_array($fileExt, ['ppt', 'pptx']);
                $isText = in_array($fileExt, ['txt', 'md']);
                $isViewable = $isPDF || $isWord || $isExcel || $isPowerPoint || $isText;
                
                echo "<div style='background: white; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6; margin-bottom: 10px;'>";
                echo "<h5>📄 " . htmlspecialchars($attachment['original_name']) . "</h5>";
                echo "<ul>";
                echo "<li><strong>Filename:</strong> " . htmlspecialchars($attachment['filename']) . "</li>";
                echo "<li><strong>Extension:</strong> $fileExt</li>";
                echo "<li><strong>MIME Type:</strong> " . $attachment['mime_type'] . "</li>";
                echo "<li><strong>Size:</strong> " . number_format($attachment['file_size']) . " bytes</li>";
                echo "<li><strong>Is Image:</strong> " . ($isImage ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is PDF:</strong> " . ($isPDF ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is Word:</strong> " . ($isWord ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is Excel:</strong> " . ($isExcel ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is PowerPoint:</strong> " . ($isPowerPoint ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is Text:</strong> " . ($isText ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Is Viewable:</strong> " . ($isViewable ? '✅ Yes' : '❌ No') . "</li>";
                echo "</ul>";
                
                // Show what buttons should be generated
                echo "<h6>🔘 Buttons that should be generated:</h6>";
                if ($isImage) {
                    echo "• Image preview with click to open modal<br>";
                }
                if ($isViewable) {
                    echo "• <button class='btn btn-sm btn-primary'><i class='fas fa-eye'></i> Xem</button><br>";
                }
                echo "• <a href='uploads/requests/" . htmlspecialchars($attachment['filename']) . "' class='btn btn-sm btn-secondary' download><i class='fas fa-download'></i> Tải về</a><br>";
                
                echo "</div>";
            }
            
        } else {
            echo "<p style='color: orange;'>⚠️ No attachments found for request 27</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Request 27 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h3>🔧 JavaScript Debug:</h3>";
echo "<p>The view button should appear if:</p>";
echo "<ol>";
echo "<li>File extension is detected correctly</li>";
echo "<li>isViewable variable is true</li>";
echo "<li>JavaScript is loading properly</li>";
echo "<li>CSS is applied correctly</li>";
echo "</ol>";

echo "<h3>🎯 Quick Test:</h3>";
echo "<p><a href='request-detail.html?id=27' target='_blank' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>";
echo "<i class='fas fa-external-link-alt'></i> Open Request 27 in New Tab";
echo "</a></p>";

echo "<p>Then check:</p>";
echo "<ul>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Check Console for JavaScript errors</li>";
echo "<li>Inspect the attachment section</li>";
echo "<li>Look for the 'Xem' button in the HTML</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
