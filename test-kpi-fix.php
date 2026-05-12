<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test KPI Fix</title>
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <h1>Test KPI Modal Fix</h1>
    
    <div class="test-section">
        <h3>Bootstrap Check</h3>
        <div id="bootstrap-status">Checking...</div>
    </div>
    
    <div class="test-section">
        <h3>Modal Element Check</h3>
        <div id="modal-status">Checking...</div>
    </div>
    
    <div class="test-section">
        <h3>Button Test</h3>
        <button id="configKPIBtn" class="btn btn-secondary" onclick="showKPIConfigModal()">
            <i class="fas fa-cogs"></i> <span>Cài KPI</span>
        </button>
        <button class="btn btn-primary" onclick="testModalDirectly()">Test Direct Modal</button>
    </div>
    
    <div class="test-section">
        <h3>Console Output</h3>
        <div id="console-output" style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; min-height: 100px; max-height: 300px; overflow-y: auto;"></div>
    </div>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Test Modal -->
    <div class="modal fade" id="kpiConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-cogs me-2"></i>Cấu hình KPI
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Test modal content - KPI Configuration</p>
                    <p>If you can see this modal, the fix is working!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Console output function
        function log(message) {
            const output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            output.innerHTML += `[${timestamp}] ${message}<br>`;
            output.scrollTop = output.scrollHeight;
            console.log(message);
        }
        
        // Manual trigger function for KPI modal
        function showKPIConfigModal() {
            log('showKPIConfigModal called');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    log('Modal shown successfully');
                } else {
                    log('ERROR: Modal element not found');
                }
            } catch (error) {
                log('ERROR: Error showing modal: ' + error.message);
                // Fallback: try using Bootstrap data attributes
                const modal = bootstrap.Modal.getInstance(document.getElementById('kpiConfigModal'));
                if (modal) {
                    modal.show();
                    log('Modal shown using fallback method');
                } else {
                    log('ERROR: Fallback also failed');
                }
            }
        }
        
        function testModalDirectly() {
            log('Testing modal directly...');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                log('Direct modal test successful');
            } catch (error) {
                log('Direct modal test failed: ' + error.message);
            }
        }
        
        // Run checks when page loads
        document.addEventListener('DOMContentLoaded', function() {
            log('Page loaded, running checks...');
            
            // Check Bootstrap
            if (typeof bootstrap !== 'undefined') {
                document.getElementById('bootstrap-status').innerHTML = '<span class="success">✅ Bootstrap 5.3.0 loaded successfully</span>';
                log('Bootstrap check: PASSED');
            } else {
                document.getElementById('bootstrap-status').innerHTML = '<span class="error">❌ Bootstrap not loaded</span>';
                log('Bootstrap check: FAILED');
            }
            
            // Check modal element
            const modalElement = document.getElementById('kpiConfigModal');
            if (modalElement) {
                document.getElementById('modal-status').innerHTML = '<span class="success">✅ Modal element found</span>';
                log('Modal element check: PASSED');
            } else {
                document.getElementById('modal-status').innerHTML = '<span class="error">❌ Modal element not found</span>';
                log('Modal element check: FAILED');
            }
            
            // Test button event listener
            const button = document.getElementById('configKPIBtn');
            if (button) {
                button.addEventListener('click', function() {
                    log('Button click event triggered');
                });
                log('Button event listener attached');
            }
        });
    </script>
</body>
</html>
