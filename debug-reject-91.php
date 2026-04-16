<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "<h2>Debug Reject Request #91 Attachments</h2>";

// Check if reject request #91 exists
$query = "SELECT id FROM reject_requests WHERE id = 91";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Reject request #91 exists:</strong> " . ($result ? 'YES' : 'NO') . "</p>";

// Check attachments for reject request #91
$query = "SELECT COUNT(*) as count FROM reject_request_attachments WHERE reject_request_id = 91";
$stmt = $db->prepare($query);
$stmt->execute();
$count = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Attachment count:</strong> " . $count['count'] . "</p>";

// List all attachments
$query = "SELECT id, original_name, filename, file_size, mime_type FROM reject_request_attachments WHERE reject_request_id = 91 ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Attachment Details:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>File Size</th><th>MIME Type</th></tr>";

foreach ($attachments as $att) {
    echo "<tr>";
    echo "<td>" . $att['id'] . "</td>";
    echo "<td>" . htmlspecialchars($att['original_name']) . "</td>";
    echo "<td>" . htmlspecialchars($att['filename']) . "</td>";
    echo "<td>" . $att['file_size'] . "</td>";
    echo "<td>" . $att['mime_type'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the main query with GROUP_CONCAT
$main_query = "SELECT rr.id, 
                      GROUP_CONCAT(CONCAT(attachment.original_name, '|', attachment.filename, '|', attachment.file_size, '|', attachment.mime_type) SEPARATOR '||') as attachments
               FROM reject_requests rr
               LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
               WHERE rr.id = 91
               GROUP BY rr.id";

$stmt = $db->prepare($main_query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Main Query Result:</h3>";
echo "<p><strong>Attachments string:</strong> " . htmlspecialchars($result['attachments']) . "</p>";

// Test processing the attachment string
if (!empty($result['attachments'])) {
    $attachment_strings = explode('||', $result['attachments']);
    echo "<h3>Processed Attachments:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Index</th><th>String</th><th>Parts Count</th><th>Original Name</th><th>Filename</th><th>Duplicate?</th></tr>";
    
    $seen_names = [];
    foreach ($attachment_strings as $i => $attachment_string) {
        $parts = explode('|', $attachment_string);
        $original_name = count($parts) >= 1 ? trim($parts[0]) : '';
        $filename = count($parts) >= 2 ? trim($parts[1]) : '';
        $is_duplicate = in_array($original_name, $seen_names);
        
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . htmlspecialchars($attachment_string) . "</td>";
        echo "<td>" . count($parts) . "</td>";
        echo "<td>" . htmlspecialchars($original_name) . "</td>";
        echo "<td>" . htmlspecialchars($filename) . "</td>";
        echo "<td style='color: " . ($is_duplicate ? 'red' : 'green') . ";'>" . ($is_duplicate ? 'DUPLICATE' : 'UNIQUE') . "</td>";
        echo "</tr>";
        
        $seen_names[] = $original_name;
    }
    echo "</table>";
} else {
    echo "<p>No attachments found in GROUP_CONCAT result</p>";
}

// Check for duplicate original names
echo "<h3>Duplicate Analysis:</h3>";
$query = "SELECT original_name, COUNT(*) as count 
          FROM reject_request_attachments 
          WHERE reject_request_id = 91 
          GROUP BY original_name 
          HAVING count > 1";
$stmt = $db->prepare($query);
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "<p style='color: red;'><strong>Duplicates found:</strong></p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Original Name</th><th>Count</th></tr>";
    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dup['original_name']) . "</td>";
        echo "<td>" . $dup['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'><strong>No duplicates found in database</strong></p>";
}
?>
