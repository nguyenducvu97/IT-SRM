<?php
// Simple test for all 3 export functions
echo "<h2>🧪 Test Simple Export Functions</h2>";

// Test 1: Summary Export
echo "<div class='test-section'>";
echo "<h3>📊 Test 1: Summary Export</h3>";
echo "<p><strong>URL:</strong> api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30</p>";
echo "<button onclick=\"window.open('api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30', '_blank')\" class='btn'>Test Summary Export</button>";
echo "<div id='summary-result'></div>";
echo "</div>";

// Test 2: Detailed Export
echo "<div class='test-section'>";
echo "<h3>📋 Test 2: Detailed Export</h3>";
echo "<p><strong>URL:</strong> api/kpi_export.php?action=export_detailed&start_date=2026-04-01&end_date=2026-04-30</p>";
echo "<button onclick=\"window.open('api/kpi_export.php?action=export_detailed&start_date=2026-04-01&end_date=2026-04-30', '_blank')\" class='btn'>Test Detailed Export</button>";
echo "<div id='detailed-result'></div>";
echo "</div>";

// Test 3: Staff Details Export
echo "<div class='test-section'>";
echo "<h3>👤 Test 3: Staff Details Export</h3>";
echo "<p><strong>URL:</strong> api/kpi_export.php?action=export_staff_details&start_date=2026-04-01&end_date=2026-04-30&staff_id=2</p>";
echo "<button onclick=\"window.open('api/kpi_export.php?action=export_staff_details&start_date=2026-04-01&end_date=2026-04-30&staff_id=2', '_blank')\" class='btn'>Test Staff Details Export</button>";
echo "<div id='staff-result'></div>";
echo "</div>";

// Test 4: Check API responses via AJAX
echo "<div class='test-section'>";
echo "<h3>🔍 Test 4: API Response Check</h3>";
echo "<button onclick='testAPIResponse()' class='btn'>Test API Responses</button>";
echo "<div id='api-results'></div>";
echo "</div>";

echo "<script>
function testAPIResponse() {
    const resultsDiv = document.getElementById('api-results');
    resultsDiv.innerHTML = '<p>Testing API responses...</p>';
    
    // Test summary API
    fetch('api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30')
        .then(response => response.text())
        .then(text => {
            if (text.includes('John Smith') && text.includes('94')) {
                resultsDiv.innerHTML += '<p class=\"success\">✅ Summary API: Working correctly</p>';
            } else {
                resultsDiv.innerHTML += '<p class=\"error\">❌ Summary API: Not working as expected</p>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML += '<p class=\"error\">❌ Summary API Error: ' + error.message + '</p>';
        });
    
    // Test detailed API
    fetch('api/kpi_export.php?action=export_detailed&start_date=2026-04-01&end_date=2026-04-30')
        .then(response => response.text())
        .then(text => {
            if (text.includes('John Smith') && text.includes('93')) {
                resultsDiv.innerHTML += '<p class=\"success\">✅ Detailed API: Working correctly</p>';
            } else {
                resultsDiv.innerHTML += '<p class=\"error\">❌ Detailed API: Not working as expected</p>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML += '<p class=\"error\">❌ Detailed API Error: ' + error.message + '</p>';
        });
    
    // Test staff details API
    fetch('api/kpi_export.php?action=export_staff_details&start_date=2026-04-01&end_date=2026-04-30&staff_id=2')
        .then(response => response.text())
        .then(text => {
            if (text.includes('Test staff')) {
                resultsDiv.innerHTML += '<p class=\"success\">✅ Staff Details API: Working correctly</p>';
            } else {
                resultsDiv.innerHTML += '<p class=\"error\">❌ Staff Details API: Not working as expected</p>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML += '<p class=\"error\">❌ Staff Details API Error: ' + error.message + '</p>';
        });
}
</script>";

echo "<style>
.test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
.success { color: green; }
.error { color: red; }
.btn { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
</style>";
?>
