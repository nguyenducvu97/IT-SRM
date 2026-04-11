<!DOCTYPE html>
<html>
<head>
    <title>Console Debug</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260411-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="app">
        <div style="padding: 20px;">
            <h1>Console Debug</h1>
            <div id="consoleOutput"></div>
        </div>
    </div>
    
    <script>
        // Override console.log to capture output
        const originalLog = console.log;
        const consoleOutput = document.getElementById('consoleOutput');
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ');
            consoleOutput.innerHTML += `<div style="background: #f0f0f0; padding: 5px; margin: 2px 0; font-family: monospace; font-size: 12px;">${message}</div>`;
        };
        
        // Test loading notifications.js
        console.log('Starting debug...');
        
        // Load notifications.js
        const script = document.createElement('script');
        script.src = 'assets/js/notifications.js?v=20260411-3';
        script.onload = () => {
            console.log('notifications.js loaded successfully');
            console.log('NotificationManager class:', typeof NotificationManager);
            
            if (typeof NotificationManager !== 'undefined') {
                console.log('Creating NotificationManager instance...');
                try {
                    const manager = new NotificationManager();
                    console.log('NotificationManager instance created:', manager);
                    console.log('Manager properties:', {
                        notificationCount: !!manager.notificationCount,
                        notificationList: !!manager.notificationList,
                        unreadCount: manager.unreadCount
                    });
                } catch (error) {
                    console.error('Error creating NotificationManager:', error);
                }
            } else {
                console.error('NotificationManager class not found');
            }
        };
        
        script.onerror = (error) => {
            console.error('Error loading notifications.js:', error);
        };
        
        document.head.appendChild(script);
    </script>
</body>
</html>
