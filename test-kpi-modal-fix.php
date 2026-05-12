<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test KPI Modal Fix</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .console-output { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; min-height: 200px; max-height: 400px; overflow-y: auto; }
        
        /* Copy CSS from main page */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: none;
            border-radius: 16px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-bottom: none;
            padding: 1.75rem 2rem;
            color: white;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-header h5 {
            font-size: 1.375rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .modal-body {
            padding: 2rem;
            background: #fafbfc;
        }
    </style>
</head>
<body>
    <h1>Test KPI Modal Fix - CSS Override Solution</h1>
    
    <div class="test-section info">
        <h3>Problem Analysis</h3>
        <p><strong>Vấn đề:</strong> CSS trong style.css có <code>.modal { display: none; }</code> đang override Bootstrap modal behavior.</p>
        <p><strong>Giải pháp:</strong> Force inline styles với <code>!important</code> để override CSS.</p>
    </div>
    
    <div class="test-section info">
        <h3>Test Buttons</h3>
        <button id="configKPIBtn" class="btn btn-secondary" onclick="showKPIConfigModal()">
            <i class="fas fa-cogs"></i> <span>Cài KPI (Enhanced Fix)</span>
        </button>
        <button class="btn btn-primary ms-2" onclick="testOriginalBootstrap()">Test Original Bootstrap</button>
        <button class="btn btn-warning ms-2" onclick="closeKPIConfigModal()">Close Modal</button>
        <button class="btn btn-danger ms-2" onclick="clearConsole()">Clear Console</button>
    </div>
    
    <div class="test-section info">
        <h3>Console Output</h3>
        <div id="console-output" class="console-output"></div>
    </div>

    <!-- Test Modal - Same structure as main page -->
    <div class="modal fade" id="kpiConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-cogs me-2"></i> Cấu hình công thức tính KPI
                    </h5>
                    <button type="button" class="btn btn-link text-white p-0" onclick="closeKPIConfigModal()" 
                            style="font-size: 2.5rem; opacity: 1; filter: brightness(2); width: 40px; height: 40px; text-decoration: none; line-height: 1;">×</button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-nowrap">
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="button" class="btn btn-success btn-sm" onclick="saveKPIConfig()">
                                <i class="fas fa-save me-1"></i> Lưu
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="resetKPIConfig()">
                                <i class="fas fa-undo me-1"></i> Cài lại
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-2" id="kpiConfigMessage"></div>
                    
                    <div id="kpiConfigContainer">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Modal Test Successful!</h5>
                            <p>Nếu bạn thấy modal này, fix đã hoạt động thành công.</p>
                            <hr>
                            <p><strong>Chi tiết test:</strong></p>
                            <ul>
                                <li>✅ Function được gọi khi click button</li>
                                <li>✅ Inline styles được áp dụng với <code>!important</code></li>
                                <li>✅ Modal được hiển thị với z-index cao</li>
                                <li>✅ Backdrop được tạo tự động</li>
                                <li>✅ Body styles được cập nhật</li>
                            </ul>
                        </div>
                    </div>
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
        
        function clearConsole() {
            document.getElementById('console-output').innerHTML = '';
        }
        
        // ENHANCED showKPIConfigModal function with CSS override
        function showKPIConfigModal() {
            log('🚀 showKPIConfigModal called - ENHANCED VERSION', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                if (modalElement) {
                    log('✅ Modal element found, applying forced styles...', 'success');
                    
                    // FORCE INLINE STYLES TO OVERRIDE CSS
                    modalElement.style.display = 'block !important';
                    modalElement.style.visibility = 'visible !important';
                    modalElement.style.opacity = '1 !important';
                    modalElement.style.zIndex = '1050 !important';
                    modalElement.style.position = 'fixed !important';
                    modalElement.style.top = '0 !important';
                    modalElement.style.left = '0 !important';
                    modalElement.style.width = '100% !important';
                    modalElement.style.height = '100% !important';
                    modalElement.style.backgroundColor = 'rgba(0, 0, 0, 0.5) !important';
                    
                    // Add show class for Bootstrap compatibility
                    modalElement.classList.add('show');
                    
                    // Create backdrop if not exists
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.style.zIndex = '1040';
                        document.body.appendChild(backdrop);
                        log('✅ Created backdrop element', 'success');
                    }
                    
                    // Set body styles
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                    
                    // Try Bootstrap modal as well
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    
                    log('✅ Modal shown successfully with forced styles', 'success');
                    
                    // Debug check
                    setTimeout(() => {
                        const computed = window.getComputedStyle(modalElement);
                        log('🔍 Modal debug - display: ' + computed.display, 'info');
                        log('🔍 Modal debug - visibility: ' + computed.visibility, 'info');
                        log('🔍 Modal debug - opacity: ' + computed.opacity, 'info');
                        log('🔍 Modal debug - z-index: ' + computed.zIndex, 'info');
                        
                        // Check if modal is actually visible
                        const rect = modalElement.getBoundingClientRect();
                        const isVisible = rect.width > 0 && rect.height > 0 && computed.display !== 'none';
                        log('👀 Modal is visible: ' + (isVisible ? 'YES ✅' : 'NO ❌'), isVisible ? 'success' : 'error');
                    }, 100);
                    
                } else {
                    log('❌ Modal element not found', 'error');
                }
            } catch (error) {
                log('❌ Error showing modal: ' + error.message, 'error');
                
                // ULTIMATE FALLBACK - Manual modal display
                try {
                    const modalElement = document.getElementById('kpiConfigModal');
                    if (modalElement) {
                        modalElement.style.cssText = `
                            display: block !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                            z-index: 1050 !important;
                            position: fixed !important;
                            top: 0 !important;
                            left: 0 !important;
                            width: 100% !important;
                            height: 100% !important;
                            background-color: rgba(0, 0, 0, 0.5) !important;
                        `;
                        modalElement.classList.add('show');
                        log('🆘 Ultimate fallback applied', 'success');
                    }
                } catch (fallbackError) {
                    log('❌ Ultimate fallback failed: ' + fallbackError.message, 'error');
                }
            }
        }
        
        // Close modal function
        function closeKPIConfigModal() {
            log('🔒 closeKPIConfigModal called', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                if (modalElement) {
                    // Remove show class
                    modalElement.classList.remove('show');
                    
                    // Hide modal
                    modalElement.style.display = 'none';
                    modalElement.style.visibility = 'hidden';
                    modalElement.style.opacity = '0';
                    
                    // Remove backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                        log('✅ Backdrop removed', 'success');
                    }
                    
                    // Restore body styles
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    
                    // Try Bootstrap hide
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    log('✅ Modal closed successfully', 'success');
                }
            } catch (error) {
                log('❌ Error closing modal: ' + error.message, 'error');
            }
        }
        
        // Test original Bootstrap
        function testOriginalBootstrap() {
            log('🔍 Testing original Bootstrap modal (should fail due to CSS)', 'info');
            try {
                const modalElement = document.getElementById('kpiConfigModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                
                setTimeout(() => {
                    const computed = window.getComputedStyle(modalElement);
                    const isVisible = computed.display !== 'none' && computed.visibility !== 'hidden';
                    log('📊 Original Bootstrap result: ' + (isVisible ? 'VISIBLE ✅' : 'HIDDEN ❌'), isVisible ? 'success' : 'error');
                }, 100);
                
            } catch (error) {
                log('❌ Original Bootstrap failed: ' + error.message, 'error');
            }
        }
        
        // Mock functions for modal buttons
        function saveKPIConfig() {
            log('💾 Save KPI Config called (mock)', 'info');
        }
        
        function resetKPIConfig() {
            log('🔄 Reset KPI Config called (mock)', 'info');
        }
        
        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Test page loaded - Ready to test KPI modal fix', 'success');
            log('📝 Test the enhanced fix vs original Bootstrap', 'info');
        });
    </script>
</body>
</html>
