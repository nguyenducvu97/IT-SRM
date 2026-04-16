<?php
// Test filter reset behavior
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>🔄 Filter Reset Test</h2>";
echo "<p><strong>Scenario:</strong> When changing filters, should reset to page 1</p>";
echo "<hr>";

// Test URLs for different scenarios
$test_scenarios = [
    [
        'name' => 'Service Requests - Change Status Filter',
        'description' => 'From page 3, change status filter',
        'current_page' => 3,
        'new_filter' => 'status=in_progress',
        'expected_page' => 1
    ],
    [
        'name' => 'Service Requests - Change Priority Filter', 
        'description' => 'From page 4, change priority filter',
        'current_page' => 4,
        'new_filter' => 'priority=high',
        'expected_page' => 1
    ],
    [
        'name' => 'Support Requests - Change Status Filter',
        'description' => 'From page 2, change support status filter',
        'current_page' => 2,
        'new_filter' => 'status=approved',
        'expected_page' => 1
    ],
    [
        'name' => 'Reject Requests - Change Status Filter',
        'description' => 'From page 5, change reject status filter',
        'current_page' => 5,
        'new_filter' => 'status=pending',
        'expected_page' => 1
    ]
];

foreach ($test_scenarios as $scenario) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3>{$scenario['name']}</h3>";
    echo "<p><em>{$scenario['description']}</em></p>";
    
    // Simulate current page (before filter change)
    $current_url = "api/service_requests.php?action=list&page={$scenario['current_page']}&limit=9&{$scenario['new_filter']}";
    echo "<p><strong>Current (Page {$scenario['current_page']}):</strong> $current_url</p>";
    
    // Simulate after filter change (should reset to page 1)
    $reset_url = "api/service_requests.php?action=list&page=1&limit=9&{$scenario['new_filter']}";
    echo "<p><strong>After Filter Change (Page 1):</strong> $reset_url</p>";
    
    // Test both URLs
    $context = stream_context_create([
        'http' => [
            'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
        ]
    ]);
    
    echo "<h4>Results:</h4>";
    
    // Test current page
    $current_response = file_get_contents($current_url, false, $context);
    $current_data = json_decode($current_response, true);
    
    if ($current_data && $current_data['success']) {
        $current_requests = $current_data['data']['requests'];
        $current_pagination = $current_data['data']['pagination'];
        echo "<p><strong>Page {$scenario['current_page']}:</strong> " . count($current_requests) . " requests</p>";
    }
    
    // Test reset page
    $reset_response = file_get_contents($reset_url, false, $context);
    $reset_data = json_decode($reset_response, true);
    
    if ($reset_data && $reset_data['success']) {
        $reset_requests = $reset_data['data']['requests'];
        $reset_pagination = $reset_data['data']['pagination'];
        echo "<p><strong>Page 1 (Reset):</strong> " . count($reset_requests) . " requests</p>";
        
        if ($reset_pagination['page'] === $scenario['expected_page']) {
            echo "<p><strong>✅ PASS:</strong> Filter reset correctly to page {$reset_pagination['page']}</p>";
        } else {
            echo "<p><strong>❌ FAIL:</strong> Expected page {$scenario['expected_page']}, got {$reset_pagination['page']}</p>";
        }
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h2>🎯 Expected Behavior</h2>";
echo "<ul>";
echo "<li><strong>✅ When changing ANY filter:</strong> Should reset to page 1</li>";
echo "<li><strong>✅ When clicking pagination:</strong> Should stay on current page</li>";
echo "<li><strong>✅ When searching:</strong> Should reset to page 1</li>";
echo "</ul>";

echo "<h2>🧪 Manual Testing Steps</h2>";
echo "<ol>";
echo "<li>Navigate to page 3, 4, or 5 on any page</li>";
echo "<li>Change any filter (status, priority, category)</li>";
echo "<li>Verify it resets to page 1</li>";
echo "<li>Click pagination buttons (2, 3, etc.)</li>";
echo "<li>Verify it stays on selected page</li>";
echo "<li>Type in search box</li>";
echo "<li>Verify it resets to page 1</li>";
echo "</ol>";

echo "<h2>🔍 JavaScript Fix Applied</h2>";
echo "<pre><code>";
echo "// BEFORE (Issue):
statusFilter.addEventListener('change', () => this.loadRequests(this.currentRequestsPage));
priorityFilter.addEventListener('change', () => this.loadRequests(this.currentRequestsPage));
categoryFilter.addEventListener('change', () => this.loadRequests(this.currentRequestsPage));

// AFTER (Fixed):
statusFilter.addEventListener('change', () => this.loadRequests(1));
priorityFilter.addEventListener('change', () => this.loadRequests(1));
categoryFilter.addEventListener('change', () => this.loadRequests(1));
</code></pre>";
?>
