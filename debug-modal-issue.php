<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Modal Issue</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .console-log { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 5px 0; }
        .modal-backdrop { z-index: 1040; }
        .modal { z-index: 1050; }
    </style>
</head>
<body>
    <h1>Debug KPI Modal Issue</h1>
    
    <div class="debug-section info">
        <h3>Modal Visibility Debug</h3>
        <div id="modal-visibility">Checking...</div>
    </div>
    
    <div class="debug-section info">
        <h3>Bootstrap Check</h3>
        <div id="bootstrap-status">Checking...</div>
    </div>
    
    <div class="debug-section info">
        <h3>Test Buttons</h3>
        <button id="configKPIBtn" class="btn btn-secondary" onclick="showKPIConfigModal()">
            <i class="fas fa-cogs"></i> <span>Cài KPI (Original Fix)</span>
        </button>
        <button class="btn btn-primary" onclick="testModalDirectly()">Test Direct Bootstrap</button>
        <button class="btn btn-primary" onclick="testModalWithForcedShow()">Force Show Modal</button>
        <button class="btn btn-primary" onclick="checkModalState()">Check Modal State</button>
        <button class="btn btn-danger" onclick="closeAllModals()">Close All Modals</button>
    </div>
    
    <div class="debug-section info">
        <h3>Console Output</h3>
        <div id="console-output" style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; min-height: 200px; max-height: 400px; overflow-y: auto;"></div>
    </div>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Test Modal - Copy from main page -->
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
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong> This is a test modal to debug the visibility issue.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Console output function
        function log(message, type = 'info') {
            const output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'black';
            output.innerHTML += `<span style="color: ${color}">[${timestamp}] ${message}</span><br>`;
            output.scrollTop = output.scrollHeight;
            console.log(message);
        }
        
        // Original fix function
        function showKPIConfigModal() {
            log('🔧 showKPIConfigModal called (Original Fix)', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                if (modalElement) {
                    log('✅ Modal element found', 'success');
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    log('✅ Modal.show() called successfully', 'success');
                    
                    // Check if modal is actually visible
                    setTimeout(() => {
                        checkModalVisibility();
                    }, 100);
                } else {
                    log('❌ Modal element not found', 'error');
                }
            } catch (error) {
                log('❌ Error showing modal: ' + error.message, 'error');
                console.error('Full error:', error);
            }
        }
        
        // Direct Bootstrap test
        function testModalDirectly() {
            log('🔍 Testing direct Bootstrap modal', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                log('✅ Direct Bootstrap test successful', 'success');
                
                setTimeout(() => {
                    checkModalVisibility();
                }, 100);
            } catch (error) {
                log('❌ Direct Bootstrap test failed: ' + error.message, 'error');
            }
        }
        
        // Force show modal with inline styles
        function testModalWithForcedShow() {
            log('🚀 Testing forced modal show', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                
                // Force inline styles
                modalElement.style.display = 'block';
                modalElement.style.visibility = 'visible';
                modalElement.style.opacity = '1';
                modalElement.style.zIndex = '1050';
                modalElement.style.position = 'fixed';
                modalElement.style.top = '0';
                modalElement.style.left = '0';
                modalElement.style.width = '100%';
                modalElement.style.height = '100%';
                
                // Add backdrop manually
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.style.zIndex = '1040';
                    document.body.appendChild(backdrop);
                }
                
                log('✅ Forced modal show with inline styles', 'success');
                
                setTimeout(() => {
                    checkModalVisibility();
                }, 100);
            } catch (error) {
                log('❌ Forced modal show failed: ' + error.message, 'error');
            }
        }
        
        // Check modal state
        function checkModalState() {
            log('🔍 Checking modal state', 'info');
            const modalElement = document.getElementById('kpiConfigModal');
            
            if (modalElement) {
                const computedStyle = window.getComputedStyle(modalElement);
                const isVisible = computedStyle.display !== 'none' && 
                                computedStyle.visibility !== 'hidden' && 
                                computedStyle.opacity !== '0';
                
                log(`Modal display: ${computedStyle.display}`, isVisible ? 'success' : 'error');
                log(`Modal visibility: ${computedStyle.visibility}`, isVisible ? 'success' : 'error');
                log(`Modal opacity: ${computedStyle.opacity}`, isVisible ? 'success' : 'error');
                log(`Modal z-index: ${computedStyle.zIndex}`, 'info');
                log(`Modal position: ${computedStyle.position}`, 'info');
                
                // Check backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                log(`Backdrop exists: ${backdrop ? 'yes' : 'no'}`, backdrop ? 'success' : 'error');
                
                if (backdrop) {
                    const backdropStyle = window.getComputedStyle(backdrop);
                    log(`Backdrop display: ${backdropStyle.display}`, 'info');
                    log(`Backdrop z-index: ${backdropStyle.zIndex}`, 'info');
                }
                
                // Check modal content
                const modalContent = modalElement.querySelector('.modal-content');
                if (modalContent) {
                    const contentStyle = window.getComputedStyle(modalContent);
                    log(`Modal content display: ${contentStyle.display}`, 'info');
                    log(`Modal content visibility: ${contentStyle.visibility}`, 'info');
                }
                
                log(`Modal is visible: ${isVisible}`, isVisible ? 'success' : 'error');
            } else {
                log('❌ Modal element not found', 'error');
            }
        }
        
        // Check modal visibility
        function checkModalVisibility() {
            const modalElement = document.getElementById('kpiConfigModal');
            const visibilityDiv = document.getElementById('modal-visibility');
            
            if (modalElement) {
                const computedStyle = window.getComputedStyle(modalElement);
                const isVisible = computedStyle.display !== 'none' && 
                                computedStyle.visibility !== 'hidden' && 
                                computedStyle.opacity !== '0';
                
                if (isVisible) {
                    visibilityDiv.innerHTML = '<span class="success">✅ Modal is VISIBLE</span>';
                    log('✅ Modal visibility check: VISIBLE', 'success');
                } else {
                    visibilityDiv.innerHTML = '<span class="error">❌ Modal is NOT visible</span>';
                    log('❌ Modal visibility check: NOT VISIBLE', 'error');
                    log(`Details: display=${computedStyle.display}, visibility=${computedStyle.visibility}, opacity=${computedStyle.opacity}`, 'error');
                }
            } else {
                visibilityDiv.innerHTML = '<span class="error">❌ Modal element not found</span>';
            }
        }
        
        // Close all modals
        function closeAllModals() {
            log('🔒 Closing all modals', 'info');
            
            // Remove backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Hide modal
            const modalElement = document.getElementById('kpiConfigModal');
            if (modalElement) {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
                
                // Remove inline styles
                modalElement.style.removeProperty('display');
                modalElement.style.removeProperty('visibility');
                modalElement.style.removeProperty('opacity');
                modalElement.style.removeProperty('z-index');
                modalElement.style.removeProperty('position');
                modalElement.style.removeProperty('top');
                modalElement.style.removeProperty('left');
                modalElement.style.removeProperty('width');
                modalElement.style.removeProperty('height');
            }
            
            // Remove body classes
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            
            log('✅ All modals closed', 'success');
            checkModalVisibility();
        }
        
        // Run checks when page loads
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Page loaded, running initial checks...', 'info');
            
            // Check Bootstrap
            if (typeof bootstrap !== 'undefined') {
                document.getElementById('bootstrap-status').innerHTML = '<span class="success">✅ Bootstrap 5.3.0 loaded successfully</span>';
                log('✅ Bootstrap check: PASSED', 'success');
            } else {
                document.getElementById('bootstrap-status').innerHTML = '<span class="error">❌ Bootstrap not loaded</span>';
                log('❌ Bootstrap check: FAILED', 'error');
            }
            
            // Check modal element
            const modalElement = document.getElementById('kpiConfigModal');
            if (modalElement) {
                log('✅ Modal element found in DOM', 'success');
            } else {
                log('❌ Modal element not found in DOM', 'error');
            }
            
            // Initial visibility check
            checkModalVisibility();
            
            log('🔍 Initial checks complete. Try clicking the buttons above.', 'info');
        });
        
        // Monitor modal events
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('kpiConfigModal');
            if (modalElement) {
                modalElement.addEventListener('show.bs.modal', function() {
                    log('📢 Bootstrap show.bs.modal event fired', 'success');
                });
                
                modalElement.addEventListener('shown.bs.modal', function() {
                    log('📢 Bootstrap shown.bs.modal event fired', 'success');
                    setTimeout(checkModalVisibility, 50);
                });
                
                modalElement.addEventListener('hide.bs.modal', function() {
                    log('📢 Bootstrap hide.bs.modal event fired', 'info');
                });
                
                modalElement.addEventListener('hidden.bs.modal', function() {
                    log('📢 Bootstrap hidden.bs.modal event fired', 'info');
                    setTimeout(checkModalVisibility, 50);
                });
            }
        });
    </script>
</body>
</html>
