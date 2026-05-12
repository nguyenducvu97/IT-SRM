<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deep CSS Debug for Modal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS that might be causing conflicts -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { 
            padding: 20px; 
            font-family: Arial, sans-serif; 
            background: #f5f5f5;
        }
        .debug-section { 
            margin: 20px 0; 
            padding: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            background: white;
        }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .console-output { 
            background: #1e1e1e; 
            color: #00ff00; 
            padding: 10px; 
            border-radius: 4px; 
            font-family: 'Courier New', monospace; 
            min-height: 300px; 
            max-height: 500px; 
            overflow-y: auto;
            font-size: 12px;
        }
        
        /* Force CSS override */
        .force-show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 999999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.8) !important;
        }
        
        .force-content {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1000000 !important;
            position: relative !important;
            background: white !important;
            margin: 50px auto !important;
            padding: 20px !important;
            border-radius: 8px !important;
            max-width: 800px !important;
            width: 90% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
        }
    </style>
</head>
<body>
    <h1>🔍 Deep CSS Debug for KPI Modal</h1>
    
    <div class="debug-section warning">
        <h3>⚠️ Problem Analysis</h3>
        <p><strong>Issue:</strong> Modal không hiển thị dù function được gọi và Bootstrap loaded.</p>
        <p><strong>Suspected Causes:</strong></p>
        <ul>
            <li>CSS conflicts từ style.css</li>
            <li>Multiple Bootstrap instances</li>
            <li>Z-index issues</li>
            <li>DOM structure problems</li>
            <li>JavaScript timing issues</li>
        </ul>
    </div>
    
    <div class="debug-section info">
        <h3>🧪 Test Buttons</h3>
        <button id="configKPIBtn" class="btn btn-secondary" onclick="debugModalStep1()">
            <i class="fas fa-cogs"></i> <span>Step 1: Basic Debug</span>
        </button>
        <button class="btn btn-primary ms-2" onclick="debugModalStep2()">Step 2: CSS Override</button>
        <button class="btn btn-warning ms-2" onclick="debugModalStep3()">Step 3: Force Show</button>
        <button class="btn btn-success ms-2" onclick="debugModalStep4()">Step 4: DOM Manipulation</button>
        <button class="btn btn-danger ms-2" onclick="forceCloseAll()">Force Close All</button>
        <button class="btn btn-dark ms-2" onclick="clearConsole()">Clear Console</button>
    </div>
    
    <div class="debug-section info">
        <h3>📊 Console Output</h3>
        <div id="console-output" class="console-output"></div>
    </div>

    <!-- Test Modal -->
    <div class="modal fade" id="kpiConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-cogs me-2"></i> Cấu hình công thức tính KPI
                    </h5>
                    <button type="button" class="btn btn-link text-white p-0" onclick="forceCloseAll()" 
                            style="font-size: 2.5rem; opacity: 1; filter: brightness(2); width: 40px; height: 40px; text-decoration: none; line-height: 1;">×</button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle me-2"></i>MODAL DEBUG SUCCESS!</h4>
                        <p>Nếu bạn thấy modal này, debug đã thành công!</p>
                        <hr>
                        <div id="debug-info">
                            <!-- Debug info sẽ được thêm vào đây -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let debugStep = 0;
        
        function log(message, type = 'info') {
            const output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff6b6b' : type === 'success' ? '#51cf66' : type === 'warning' ? '#ffd43b' : '#74c0fc';
            output.innerHTML += `<span style="color: ${color}">[${timestamp}] [STEP ${debugStep}] ${message}</span><br>`;
            output.scrollTop = output.scrollHeight;
            console.log(`[STEP ${debugStep}] ${message}`);
        }
        
        function clearConsole() {
            document.getElementById('console-output').innerHTML = '';
            debugStep = 0;
        }
        
        // STEP 1: Basic Debug
        function debugModalStep1() {
            debugStep = 1;
            log('🔍 STEP 1: Basic Debug - Checking DOM and Bootstrap', 'info');
            
            const modalElement = document.getElementById('kpiConfigModal');
            if (!modalElement) {
                log('❌ Modal element NOT found in DOM', 'error');
                return;
            }
            
            log('✅ Modal element found in DOM', 'success');
            log(`📍 Modal position: ${modalElement.tagName}#${modalElement.id}`, 'info');
            log(`🎨 Modal classes: ${modalElement.className}`, 'info');
            
            // Check Bootstrap
            if (typeof bootstrap !== 'undefined') {
                log('✅ Bootstrap loaded', 'success');
                try {
                    const modal = new bootstrap.Modal(modalElement);
                    log('✅ Bootstrap Modal instance created', 'success');
                    modal.show();
                    log('🚀 Bootstrap modal.show() called', 'info');
                    
                    setTimeout(() => checkModalVisibility('Bootstrap'), 500);
                } catch (error) {
                    log('❌ Bootstrap Modal creation failed: ' + error.message, 'error');
                }
            } else {
                log('❌ Bootstrap NOT loaded', 'error');
            }
        }
        
        // STEP 2: CSS Override
        function debugModalStep2() {
            debugStep = 2;
            log('🎨 STEP 2: CSS Override - Forcing inline styles', 'info');
            
            const modalElement = document.getElementById('kpiConfigModal');
            if (!modalElement) {
                log('❌ Modal element not found', 'error');
                return;
            }
            
            log('🔧 Applying CSS overrides...', 'info');
            
            // Get current computed styles
            const computed = window.getComputedStyle(modalElement);
            log(`📊 Current display: ${computed.display}`, 'info');
            log(`📊 Current visibility: ${computed.visibility}`, 'info');
            log(`📊 Current opacity: ${computed.opacity}`, 'info');
            log(`📊 Current z-index: ${computed.zIndex}`, 'info');
            
            // Force inline styles
            modalElement.style.setProperty('display', 'block', 'important');
            modalElement.style.setProperty('visibility', 'visible', 'important');
            modalElement.style.setProperty('opacity', '1', 'important');
            modalElement.style.setProperty('z-index', '99999', 'important');
            modalElement.style.setProperty('position', 'fixed', 'important');
            modalElement.style.setProperty('top', '0', 'important');
            modalElement.style.setProperty('left', '0', 'important');
            modalElement.style.setProperty('width', '100%', 'important');
            modalElement.style.setProperty('height', '100%', 'important');
            modalElement.style.setProperty('background-color', 'rgba(0, 0, 0, 0.8)', 'important');
            
            modalElement.classList.add('show');
            
            log('✅ CSS overrides applied', 'success');
            
            // Create backdrop
            createBackdrop();
            
            setTimeout(() => checkModalVisibility('CSS Override'), 500);
        }
        
        // STEP 3: Force Show with CSS classes
        function debugModalStep3() {
            debugStep = 3;
            log('⚡ STEP 3: Force Show - Using CSS classes', 'info');
            
            const modalElement = document.getElementById('kpiConfigModal');
            if (!modalElement) {
                log('❌ Modal element not found', 'error');
                return;
            }
            
            // Add force-show class
            modalElement.classList.add('force-show');
            
            // Also force content
            const modalContent = modalElement.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.add('force-content');
                log('✅ Force classes added to modal and content', 'success');
            } else {
                log('❌ Modal content not found', 'error');
            }
            
            // Create backdrop
            createBackdrop();
            
            // Set body styles
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            log('✅ Force show applied', 'success');
            
            setTimeout(() => checkModalVisibility('Force Show'), 500);
        }
        
        // STEP 4: DOM Manipulation
        function debugModalStep4() {
            debugStep = 4;
            log('🔧 STEP 4: DOM Manipulation - Moving modal to body', 'info');
            
            const modalElement = document.getElementById('kpiConfigModal');
            if (!modalElement) {
                log('❌ Modal element not found', 'error');
                return;
            }
            
            // Check if modal is nested
            if (modalElement.parentElement !== document.body) {
                log(`📍 Modal is nested in: ${modalElement.parentElement.tagName}`, 'warning');
                log('🔄 Moving modal to document.body...', 'info');
                
                // Move to body
                document.body.appendChild(modalElement);
                log('✅ Modal moved to document.body', 'success');
            } else {
                log('✅ Modal already in document.body', 'success');
            }
            
            // Apply all force methods
            modalElement.classList.add('force-show');
            modalElement.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                z-index: 999999 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0, 0, 0, 0.8) !important;
            `;
            
            const modalContent = modalElement.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.add('force-content');
            }
            
            createBackdrop();
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            log('✅ DOM manipulation complete', 'success');
            
            setTimeout(() => checkModalVisibility('DOM Manipulation'), 500);
        }
        
        function createBackdrop() {
            // Remove existing backdrop
            const existingBackdrop = document.querySelector('.modal-backdrop');
            if (existingBackdrop) {
                existingBackdrop.remove();
            }
            
            // Create new backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 99998 !important;
                background-color: rgba(0, 0, 0, 0.5) !important;
            `;
            document.body.appendChild(backdrop);
            log('✅ Backdrop created', 'success');
        }
        
        function checkModalVisibility(method) {
            const modalElement = document.getElementById('kpiConfigModal');
            if (!modalElement) {
                log('❌ Cannot check visibility - modal not found', 'error');
                return;
            }
            
            const computed = window.getComputedStyle(modalElement);
            const rect = modalElement.getBoundingClientRect();
            
            log(`🔍 [${method}] Visibility Check:`, 'info');
            log(`   Display: ${computed.display}`, computed.display !== 'none' ? 'success' : 'error');
            log(`   Visibility: ${computed.visibility}`, computed.visibility !== 'hidden' ? 'success' : 'error');
            log(`   Opacity: ${computed.opacity}`, parseFloat(computed.opacity) > 0 ? 'success' : 'error');
            log(`   Z-index: ${computed.zIndex}`, parseInt(computed.zIndex) > 1000 ? 'success' : 'error');
            log(`   Position: ${computed.position}`, computed.position === 'fixed' ? 'success' : 'error');
            log(`   Width: ${rect.width}px`, rect.width > 0 ? 'success' : 'error');
            log(`   Height: ${rect.height}px`, rect.height > 0 ? 'success' : 'error');
            
            const isVisible = computed.display !== 'none' && 
                            computed.visibility !== 'hidden' && 
                            parseFloat(computed.opacity) > 0 && 
                            rect.width > 0 && 
                            rect.height > 0;
            
            log(`   RESULT: ${isVisible ? 'VISIBLE ✅' : 'HIDDEN ❌'}`, isVisible ? 'success' : 'error');
            
            // Update debug info in modal if visible
            if (isVisible) {
                const debugInfo = document.getElementById('debug-info');
                if (debugInfo) {
                    debugInfo.innerHTML = `
                        <h5><i class="fas fa-check-circle text-success me-2"></i>Modal Successfully Visible!</h5>
                        <p><strong>Method:</strong> ${method}</p>
                        <p><strong>Display:</strong> ${computed.display}</p>
                        <p><strong>Visibility:</strong> ${computed.visibility}</p>
                        <p><strong>Opacity:</strong> ${computed.opacity}</p>
                        <p><strong>Z-index:</strong> ${computed.zIndex}</p>
                        <p><strong>Dimensions:</strong> ${rect.width}x${rect.height}px</p>
                        <hr>
                        <p class="text-success"><strong>🎉 SUCCESS! Modal is now visible!</strong></p>
                    `;
                }
            }
        }
        
        function forceCloseAll() {
            log('🔒 Force closing all modals...', 'info');
            
            const modalElement = document.getElementById('kpiConfigModal');
            if (modalElement) {
                modalElement.classList.remove('show', 'force-show');
                modalElement.style.display = 'none';
                modalElement.style.visibility = 'hidden';
                modalElement.style.opacity = '0';
            }
            
            const modalContent = modalElement?.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.remove('force-content');
            }
            
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            
            log('✅ All modals force closed', 'success');
        }
        
        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Deep CSS Debug page loaded', 'success');
            log('📝 Test each step sequentially to identify the issue', 'info');
            log('🔍 Step 1: Basic Bootstrap test', 'info');
            log('🎨 Step 2: CSS override test', 'info');
            log('⚡ Step 3: Force class test', 'info');
            log('🔧 Step 4: DOM manipulation test', 'info');
        });
    </script>
</body>
</html>
