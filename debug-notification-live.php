<?php
// Live Debug for Notification Badge Issue
// This script will help identify exactly what's happening

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

echo "<h1>Live Debug: Notification Badge Issue</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Debug Checklist:</h2>";
echo "<ol>";
echo "<li>Check if notification badge element exists in DOM</li>";
echo "<li>Check if CSS is applied correctly</li>";
echo "<li>Check if JavaScript is updating the element</li>";
echo "<li>Check if element is visible (not hidden)</li>";
echo "<li>Check if element has correct positioning</li>";
echo "</ol>";
echo "</div>";

// Check database
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Database Check:</h3>";

try {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([1]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
    
    echo "<p><strong>Unread notifications in database:</strong> $unreadCount</p>";
    
    if ($unreadCount > 0) {
        echo "<p style='color: green;'>Database has unread notifications - Badge should show</p>";
    } else {
        echo "<p style='color: orange;'>Database has no unread notifications - Badge should be hidden</p>";
        
        // Create test notification if none exist
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, 'Test Notification', 'This is a test notification for debugging', 'info', 0]);
        echo "<p style='color: blue;'>Created test notification for debugging</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test API
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>API Test:</h3>";
echo "<button onclick='testAPI()' class='btn' style='background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Test Notification Count API</button>";
echo "<div id='apiResult'></div>";
echo "</div>";

// HTML Structure Check
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>HTML Structure Check:</h3>";
echo "<button onclick='checkHTML()' class='btn' style='background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Check HTML Elements</button>";
echo "<div id='htmlResult'></div>";
echo "</div>";

// CSS Check
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>CSS Check:</h3>";
echo "<button onclick='checkCSS()' class='btn' style='background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Check CSS Styles</button>";
echo "<div id='cssResult'></div>";
echo "</div>";

// JavaScript Test
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>JavaScript Test:</h3>";
echo "<button onclick='testJS()' class='btn' style='background: #6f42c1; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Test JavaScript Functions</button>";
echo "<div id='jsResult'></div>";
echo "</div>";

echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Manual Testing Steps:</h2>";
echo "<ol>";
echo "<li>Open main application in new tab: <a href='index.html' target='_blank' style='color: white; text-decoration: underline;'>Open IT Service Request</a></li>";
echo "<li>Login with test user</li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Run these commands in console:</li>";
echo "</ol>";
echo "<pre style='background: rgba(255,255,255,0.1); padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// Check if badge element exists
console.log('Badge element:', document.getElementById('notificationBadge'));
console.log('Count element:', document.getElementById('notificationCount'));

// Check computed styles
const badge = document.getElementById('notificationBadge');
if (badge) {
    const styles = window.getComputedStyle(badge);
    console.log('Badge styles:', {
        display: styles.display,
        position: styles.position,
        top: styles.top,
        right: styles.right,
        background: styles.background,
        color: styles.color,
        visibility: styles.visibility
    });
}

// Check parent positioning
const btn = document.getElementById('notificationBtn');
if (btn) {
    const btnStyles = window.getComputedStyle(btn);
    console.log('Button styles:', {
        position: btnStyles.position,
        display: btnStyles.display
    });
}

// Test manual update
if (badge) {
    badge.textContent = '5';
    badge.style.display = 'inline-block';
    console.log('Manual update applied');
}
";
echo "</pre>";
echo "</div>";

?>

<script>
function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>Testing API...</p>';
    
    fetch('api/notifications.php?action=count')
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        
        let html = '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
        html += '<p><strong>API Response:</strong></p>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        html += '<p><strong>Success:</strong> ' + (data.success ? 'YES' : 'NO') + '</p>';
        html += '<p><strong>Unread Count:</strong> ' + (data.data?.unread_count || 'N/A') + '</p>';
        html += '</div>';
        
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        console.error('API Error:', error);
        resultDiv.innerHTML = '<p style="color: red;">API Error: ' + error.message + '</p>';
    });
}

function checkHTML() {
    const resultDiv = document.getElementById('htmlResult');
    
    const badge = document.getElementById('notificationBadge');
    const count = document.getElementById('notificationCount');
    const btn = document.getElementById('notificationBtn');
    
    let html = '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
    html += '<p><strong>HTML Elements:</strong></p>';
    html += '<p>Badge element: ' + (badge ? 'EXISTS' : 'NOT FOUND') + '</p>';
    html += '<p>Count element: ' + (count ? 'EXISTS' : 'NOT FOUND') + '</p>';
    html += '<p>Button element: ' + (btn ? 'EXISTS' : 'NOT FOUND') + '</p>';
    
    if (badge) {
        html += '<p>Badge content: "' + badge.textContent + '"</p>';
        html += '<p>Badge display: ' + window.getComputedStyle(badge).display + '</p>';
        html += '<p>Badge visibility: ' + window.getComputedStyle(badge).visibility + '</p>';
    }
    
    if (count) {
        html += '<p>Count content: "' + count.textContent + '"</p>';
        html += '<p>Count classes: ' + count.className + '</p>';
    }
    
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

function checkCSS() {
    const resultDiv = document.getElementById('cssResult');
    
    const badge = document.getElementById('notificationBadge');
    
    if (!badge) {
        resultDiv.innerHTML = '<p style="color: red;">Badge element not found</p>';
        return;
    }
    
    const styles = window.getComputedStyle(badge);
    
    let html = '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
    html += '<p><strong>CSS Styles for Badge:</strong></p>';
    html += '<pre style="font-size: 11px;">';
    html += 'display: ' + styles.display + '\n';
    html += 'position: ' + styles.position + '\n';
    html += 'top: ' + styles.top + '\n';
    html += 'right: ' + styles.right + '\n';
    html += 'background: ' + styles.background + '\n';
    html += 'color: ' + styles.color + '\n';
    html += 'font-size: ' + styles.fontSize + '\n';
    html += 'font-weight: ' + styles.fontWeight + '\n';
    html += 'padding: ' + styles.padding + '\n';
    html += 'border-radius: ' + styles.borderRadius + '\n';
    html += 'min-width: ' + styles.minWidth + '\n';
    html += 'text-align: ' + styles.textAlign + '\n';
    html += 'visibility: ' + styles.visibility + '\n';
    html += 'transform: ' + styles.transform + '\n';
    html += 'z-index: ' + styles.zIndex + '\n';
    html += '</pre>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

function testJS() {
    const resultDiv = document.getElementById('jsResult');
    
    // Test if functions exist
    const hasApp = typeof window.app !== 'undefined';
    const hasUpdateCount = hasApp && typeof window.app.updateNotificationCount === 'function';
    const hasUpdateDisplay = hasApp && typeof window.app.updateNotificationCountDisplay === 'function';
    
    let html = '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
    html += '<p><strong>JavaScript Check:</strong></p>';
    html += '<p>Window.app exists: ' + (hasApp ? 'YES' : 'NO') + '</p>';
    html += '<p>updateNotificationCount exists: ' + (hasUpdateCount ? 'YES' : 'NO') + '</p>';
    html += '<p>updateNotificationCountDisplay exists: ' + (hasUpdateDisplay ? 'YES' : 'NO') + '</p>';
    
    if (hasUpdateDisplay) {
        // Test manual update
        try {
            window.app.updateNotificationCountDisplay(5);
            html += '<p style="color: green;">Manual update test: SUCCESS</p>';
        } catch (error) {
            html += '<p style="color: red;">Manual update test: ERROR - ' + error.message + '</p>';
        }
    }
    
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Auto-refresh every 10 seconds
setTimeout(() => {
    location.reload();
}, 10000);
</script>

<style>
.btn {
    cursor: pointer;
    transition: background-color 0.3s;
    margin: 5px;
}
.btn:hover {
    opacity: 0.8;
}
</style>
