<?php
// Script to clean up duplicate attachments for reject request ID 58
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Clean Up Duplicate Attachments - Reject Request ID 58</h2>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    // Step 1: Show current attachments
    echo "<h3>Step 1: Current Attachments</h3>";
    $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at 
                        FROM reject_request_attachments 
                        WHERE reject_request_id = :reject_request_id 
                        ORDER BY uploaded_at ASC";
    $attachment_stmt = $db->prepare($attachment_query);
    $attachment_stmt->bindValue(':reject_request_id', 58, PDO::PARAM_INT);
    $attachment_stmt->execute();
    $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total attachments:</strong> " . count($attachments) . "</p>";
    
    // Group by original name to find duplicates
    $grouped = [];
    foreach ($attachments as $attachment) {
        $key = $attachment['original_name'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $attachment;
    }
    
    echo "<h4>Attachments Grouped by Original Name:</h4>";
    foreach ($grouped as $original_name => $files) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>Original Name:</strong> " . $original_name . "<br>";
        echo "<strong>Count:</strong> " . count($files) . "<br>";
        
        if (count($files) > 1) {
            echo "<p style='color: red;'><strong>DUPLICATE FOUND!</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Filename</th><th>Size</th><th>Uploaded At</th><th>Action</th></tr>";
            
            // Keep the first one (earliest), mark others for deletion
            $files_to_keep = array_shift($files);
            echo "<tr style='background: #d4edda;'>";
            echo "<td>" . $files_to_keep['id'] . "</td>";
            echo "<td>" . $files_to_keep['filename'] . "</td>";
            echo "<td>" . number_format($files_to_keep['file_size']) . "</td>";
            echo "<td>" . $files_to_keep['uploaded_at'] . "</td>";
            echo "<td style='color: green;'>KEEP</td>";
            echo "</tr>";
            
            foreach ($files as $file) {
                echo "<tr style='background: #f8d7da;'>";
                echo "<td>" . $file['id'] . "</td>";
                echo "<td>" . $file['filename'] . "</td>";
                echo "<td>" . number_format($file['file_size']) . "</td>";
                echo "<td>" . $file['uploaded_at'] . "</td>";
                echo "<td style='color: red;'>DELETE</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: green;'>No duplicates</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Filename</th><th>Size</th><th>Uploaded At</th></tr>";
            echo "<tr>";
            echo "<td>" . $files[0]['id'] . "</td>";
            echo "<td>" . $files[0]['filename'] . "</td>";
            echo "<td>" . number_format($files[0]['file_size']) . "</td>";
            echo "<td>" . $files[0]['uploaded_at'] . "</td>";
            echo "</tr>";
            echo "</table>";
        }
        
        echo "</div>";
    }
    
    // Step 2: Clean up duplicates
    if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'confirm') {
        echo "<h3>Step 2: Cleaning Up Duplicates</h3>";
        
        $db->beginTransaction();
        
        try {
            $deleted_count = 0;
            
            foreach ($grouped as $original_name => $files) {
                if (count($files) > 1) {
                    // Keep the first one, delete the rest
                    $files_to_keep = array_shift($files);
                    
                    foreach ($files as $file) {
                        // Delete from database
                        $delete_query = "DELETE FROM reject_request_attachments WHERE id = :id";
                        $delete_stmt = $db->prepare($delete_query);
                        $delete_stmt->bindValue(':id', $file['id'], PDO::PARAM_INT);
                        $delete_stmt->execute();
                        
                        // Delete physical file
                        $file_path = __DIR__ . '/../uploads/reject_requests/' . $file['filename'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                            echo "<p>Deleted file: " . $file['filename'] . "</p>";
                        }
                        
                        $deleted_count++;
                        echo "<p style='color: orange;'>Deleted duplicate attachment ID " . $file['id'] . " (" . $file['original_name'] . ")</p>";
                    }
                }
            }
            
            $db->commit();
            echo "<p style='color: green;'><strong>Cleanup completed! Deleted " . $deleted_count . " duplicate attachments.</strong></p>";
            
            // Show final result
            echo "<h4>Final Attachments:</h4>";
            $final_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at 
                            FROM reject_request_attachments 
                            WHERE reject_request_id = :reject_request_id 
                            ORDER BY uploaded_at ASC";
            $final_stmt = $db->prepare($final_query);
            $final_stmt->bindValue(':reject_request_id', 58, PDO::PARAM_INT);
            $final_stmt->execute();
            $final_attachments = $final_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Final count:</strong> " . count($final_attachments) . "</p>";
            foreach ($final_attachments as $attachment) {
                echo "- " . $attachment['original_name'] . " (" . number_format($attachment['file_size']) . " bytes)<br>";
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<p style='color: red;'><strong>Error during cleanup:</strong> " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<h3>Step 2: Confirm Cleanup</h3>";
        echo "<p style='color: orange;'><strong>⚠️ WARNING: This will permanently delete duplicate attachments!</strong></p>";
        echo "<p><a href='?cleanup=confirm' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none;'>🗑️ Confirm Cleanup</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
