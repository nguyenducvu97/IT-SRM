<!DOCTYPE html>
<html>
<head>
    <title>Notification Singleton Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Notification Manager Singleton Test</h1>
    
    <div class="test-section info">
        <h3>📋 Problem Solved</h3>
        <p><strong>Vấn đề:</strong> Số lượng thông báo bị lặp và lỗi background ở trang index.html</p>
        <p><strong>Nguyên nhân:</strong> Multiple instances của NotificationManager được tạo, gây ra:</p>
        <ul>
            <li>Nhiều auto-refresh intervals chạy đồng thời</li>
            <li>Nhiều API calls cùng lúc</li>
            <li>Background processes conflict</li>
            <li>Memory leaks</li>
        </ul>
    </div>

    <div class="test-section success">
        <h3>🔧 Solution Applied</h3>
        <p><strong>Singleton Pattern:</strong> Chỉ cho phép một instance duy nhất của NotificationManager</p>
        <pre><code>class NotificationManager {
    constructor() {
        // Singleton pattern - prevent multiple instances
        if (NotificationManager.instance) {
            console.log('Instance already exists, returning existing instance');
            return NotificationManager.instance;
        }
        
        // Create new instance and store it
        NotificationManager.instance = this;
        this.init();
    }
}</code></pre>
    </div>

    <div class="test-section info">
        <h3>🧪 Test Steps</h3>
        <ol>
            <li>Mở trang index.html</li>
            <li>Mở console (F12)</li>
            <li>Kiểm tra các log messages:</li>
            <li>Chuyển trang và quay lại</li>
            <li>Kiểm tra xem có duplicate instance không</li>
        </ol>
    </div>

    <div class="test-section">
        <h3>📊 Expected Console Output</h3>
        <div class="success">
            <p><strong>✅ First page load:</strong></p>
            <pre>NotificationManager: Creating new instance
Index: NotificationManager singleton instance created successfully!</pre>
            
            <p><strong>✅ Second page load (should return existing):</strong></p>
            <pre>Index: Clearing existing singleton instance...
NotificationManager: Destroying instance...
NotificationManager: Creating new instance
Index: NotificationManager singleton instance created successfully!</pre>
            
            <p><strong>❌ Should NOT see:</strong></p>
            <pre>NotificationManager: Instance already exists, returning existing instance</pre>
        </div>
    </div>

    <div class="test-section warning">
        <h3>⚠️ Important Notes</h3>
        <ul>
            <li><strong>Single Instance:</strong> Chỉ có một NotificationManager chạy tại một thời điểm</li>
            <li><strong>Auto-refresh:</strong> Chỉ có một interval chạy (30 giây)</li>
            <li><strong>Memory:</strong> Không có memory leaks từ multiple instances</li>
            <li><strong>Performance:</strong> Giảm load trên server và client</li>
            <li><strong>Background:</strong> Không còn conflict processes</li>
        </ul>
    </div>

    <div class="test-section success">
        <h3>📁 Files Updated</h3>
        <ul>
            <li><code>assets/js/notifications.js</code> - Added singleton pattern</li>
            <li><code>index.html</code> - Updated to use clearInstance()</li>
            <li><code>request-detail.html</code> - Updated to use clearInstance()</li>
            <li><strong>Version updates:</strong></li>
            <ul>
                <li>notifications.js: v=20260411-6 → v=20260411-7</li>
                <li>Force browser cache refresh</li>
            </ul>
        </ul>
    </div>

    <div class="test-section info">
        <h3>🎯 Expected Results</h3>
        <ul>
            <li>✅ <strong>Không lặp số lượng thông báo:</strong> Chỉ một instance đếm</li>
            <li>✅ <strong>Không lỗi background:</strong> Chỉ một interval chạy</li>
            <li>✅ <strong>Performance tốt:</strong> Giảm API calls</li>
            <li>✅ <strong>Memory ổn định:</strong> Không có leaks</li>
            <li>✅ <strong>Console sạch:</strong> Chỉ có các log cần thiết</li>
        </ul>
    </div>

    <script>
        // Test singleton pattern locally
        console.log('🧪 Testing NotificationManager Singleton Pattern...');
        
        // Simulate multiple instance creation
        console.log('Creating first instance...');
        const instance1 = new NotificationManager();
        
        console.log('Creating second instance (should return first)...');
        const instance2 = new NotificationManager();
        
        console.log('Creating third instance (should return first)...');
        const instance3 = new NotificationManager();
        
        // Check if all instances are the same
        if (instance1 === instance2 && instance2 === instance3) {
            console.log('✅ Singleton pattern working: All instances are the same');
            document.body.innerHTML += '<div class="test-section success"><h3>✅ Test Result: SUCCESS</h3><p>Singleton pattern is working correctly!</p></div>';
        } else {
            console.error('❌ Singleton pattern failed: Different instances created');
            document.body.innerHTML += '<div class="test-section error"><h3>❌ Test Result: FAILED</h3><p>Singleton pattern is not working!</p></div>';
        }
        
        // Test clearInstance
        console.log('Testing clearInstance...');
        NotificationManager.clearInstance();
        
        console.log('Creating new instance after clear...');
        const instance4 = new NotificationManager();
        
        if (instance1 !== instance4) {
            console.log('✅ Clear and recreate working: New instance created');
        } else {
            console.error('❌ Clear and recreate failed: Same instance returned');
        }
    </script>
</body>
</html>
