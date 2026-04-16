<?php
// Quick fix to add pagination container to reject requests page
echo "<h2>Fixing Reject Requests Pagination</h2>";

$file_path = 'index.html';
$content = file_get_contents($file_path);

if ($content) {
    // Find rejectRequestsList div and add pagination after it
    $pattern = '/(<div id="rejectRequestsList" class="request-list">.*?<\/div>)/s';
    
    if (preg_match($pattern, $content)) {
        $replacement = '$1' . "\n                    \n                    <div id=\"rejectPagination\" class=\"pagination\">\n                        <!-- Pagination will be loaded here -->\n                    </div>";
        
        $new_content = preg_replace($pattern, $replacement, $content);
        
        if ($new_content && $new_content !== $content) {
            if (file_put_contents($file_path, $new_content)) {
                echo "<p style='color: green;'>✅ Successfully added rejectPagination container!</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to write file</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Pattern replacement failed or already exists</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not find rejectRequestsList pattern</p>";
    }
    
    // Verify the addition
    $updated_content = file_get_contents($file_path);
    if (strpos($updated_content, 'id="rejectPagination"') !== false) {
        echo "<p style='color: green;'>✅ Verification: rejectPagination container found!</p>";
    } else {
        echo "<p style='color: red;'>❌ Verification: rejectPagination container NOT found!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Could not read index.html</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Refresh browser (Ctrl+F5) to load updated HTML</li>";
echo "<li>Navigate to 'Yêu cầu từ chối' page</li>";
echo "<li>Check if pagination appears at bottom</li>";
echo "<li>Test pagination buttons</li>";
echo "</ol>";
?>
