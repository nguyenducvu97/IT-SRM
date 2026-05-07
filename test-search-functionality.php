<?php
// Test Search Functionality
header("Content-Type: text/plain; charset=utf-8");

echo "=== TEST SEARCH FUNCTIONALITY ===\n\n";

// Test different search scenarios
$test_cases = [
    [
        'name' => 'Search by title',
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=test&limit=3',
        'expected_fields' => ['title']
    ],
    [
        'name' => 'Search by description', 
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=hardware&limit=3',
        'expected_fields' => ['description']
    ],
    [
        'name' => 'Search by username',
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=ndvu&limit=3',
        'expected_fields' => ['username']
    ],
    [
        'name' => 'Search by request ID',
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=1025&limit=3',
        'expected_fields' => ['id']
    ],
    [
        'name' => 'Search with date filter',
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=test&start_date=2024-01-01&end_date=2024-12-31&limit=3',
        'expected_fields' => ['title', 'start_date', 'end_date']
    ],
    [
        'name' => 'Search with status filter',
        'url' => 'http://localhost/it-service-request/api/search_requests.php?search=test&status=open&limit=3',
        'expected_fields' => ['title', 'status']
    ]
];

foreach ($test_cases as $i => $test) {
    echo ($i + 1) . ". {$test['name']}:\n";
    echo "URL: {$test['url']}\n";
    
    $context = stream_context_create([
        'http' => [
            'header' => "Cookie: PHPSESSID=test_session"
        ]
    ]);
    
    $response = file_get_contents($test['url'], false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✓ SUCCESS: Found {$data['data']['pagination']['total']} requests\n";
            
            // Check if results contain search terms
            if (!empty($data['data']['requests'])) {
                $first_request = $data['data']['requests'][0] ?? null;
                if ($first_request) {
                    echo "  First result: ID {$first_request['id']} - {$first_request['title']}\n";
                }
            }
        } else {
            echo "✗ FAILED: {$data['message']}\n";
        }
    } else {
        echo "✗ ERROR: No response received\n";
    }
    
    echo "\n";
}

echo "=== TEST COMPLETE ===\n";
?>
