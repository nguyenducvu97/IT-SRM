<!DOCTYPE html>
<html>
<head>
    <title>Quick KPI Modal Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .console { background: #1e1e1e; color: #00ff00; padding: 10px; border-radius: 5px; font-family: monospace; height: 200px; overflow-y: auto; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Quick KPI Modal Test</h1>
    <button class="btn btn-primary" onclick="testModal()">Test KPI Modal</button>
    <button class="btn btn-primary" onclick="clearConsole()">Clear Console</button>
    <div id="output" class="console"></div>
    
    <!-- Bootstrap CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Test Modal -->
    <div class="modal fade" id="kpiConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">KPI Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal()"></button>
                </div>
                <div class="modal-body">
                    <h3>🎉 MODAL TEST SUCCESS!</h3>
                    <p>If you can see this modal, the fix is working!</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function log(msg) {
            const output = document.getElementById('output');
            const time = new Date().toLocaleTimeString();
            output.innerHTML += `[${time}] ${msg}<br>`;
            output.scrollTop = output.scrollHeight;
            console.log(msg);
        }
        
        function clearConsole() {
            document.getElementById('output').innerHTML = '';
        }
        
        function testModal() {
            log('🚀 Testing KPI Modal...');
            
            const modal = document.getElementById('kpiConfigModal');
            if (!modal) {
                log('❌ Modal not found');
                return;
            }
            
            log('✅ Modal found, applying force styles...');
            
            // Move to body
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
                log('✅ Modal moved to body');
            }
            
            // Force styles
            modal.style.cssText = `
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
            
            modal.classList.add('show');
            
            // Create backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 999998 !important;
                background-color: rgba(0, 0, 0, 0.3) !important;
            `;
            document.body.appendChild(backdrop);
            
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            log('✅ Modal should be visible now!');
            
            // Check visibility
            setTimeout(() => {
                const computed = window.getComputedStyle(modal);
                const rect = modal.getBoundingClientRect();
                const isVisible = computed.display !== 'none' && 
                                computed.visibility !== 'hidden' && 
                                parseFloat(computed.opacity) > 0 && 
                                rect.width > 0 && rect.height > 0;
                
                log(`🔍 Visibility check: ${isVisible ? 'VISIBLE ✅' : 'HIDDEN ❌'}`);
                log(`   Display: ${computed.display}`);
                log(`   Visibility: ${computed.visibility}`);
                log(`   Opacity: ${computed.opacity}`);
                log(`   Dimensions: ${rect.width}x${rect.height}px`);
            }, 200);
        }
        
        function closeModal() {
            log('🔒 Closing modal...');
            
            const modal = document.getElementById('kpiConfigModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
            
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            
            log('✅ Modal closed');
        }
    </script>
</body>
</html>
