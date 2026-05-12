const { app, BrowserWindow, Menu, shell, ipcMain } = require('electron');
const path = require('path');

// Server configuration - UPDATE THIS WITH YOUR SERVER IP
const SERVER_IP = '192.168.220.25:3005'; // Change this to your actual server IP
const SERVER_URL = `http://${SERVER_IP}/it-service-request`;

// Keep a global reference of the window object
let mainWindow;

function createWindow() {
    // Create the browser window
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        icon: path.join(__dirname, 'assets', 'icon.png'),
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            enableRemoteModule: false,
            preload: path.join(__dirname, 'preload.js')
        },
        show: false, // Don't show until ready-to-show
        titleBarStyle: 'default'
    });

    // Load from server instead of localhost
    console.log(`Loading application from: ${SERVER_URL}`);
    mainWindow.loadURL(SERVER_URL);

    // Show window when ready-to-show to prevent visual flash
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        
        // Open DevTools in development (remove in production)
        if (process.env.NODE_ENV === 'development') {
            mainWindow.webContents.openDevTools();
        }
    });

    // Handle server unavailable
    mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
        console.log('Failed to load:', errorCode, errorDescription);
        
        if (errorCode === -2) { // Connection refused
            // Show server error page
            mainWindow.loadFile('server-error.html');
        } else if (errorCode === -105) { // DNS lookup failed
            mainWindow.loadFile('server-error.html');
        }
    });

    // Handle window closed
    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // Handle external links
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });

    // Create menu
    const template = [
        {
            label: 'File',
            submenu: [
                {
                    label: 'New Request',
                    accelerator: 'CmdOrCtrl+N',
                    click: () => {
                        mainWindow.webContents.executeJavaScript('window.location.href = "index.html?page=requests"');
                    }
                },
                { type: 'separator' },
                {
                    label: 'Refresh',
                    accelerator: 'F5',
                    click: () => {
                        mainWindow.reload();
                    }
                },
                { type: 'separator' },
                {
                    label: 'Exit',
                    accelerator: process.platform === 'darwin' ? 'Cmd+Q' : 'Ctrl+Q',
                    click: () => {
                        app.quit();
                    }
                }
            ]
        },
        {
            label: 'View',
            submenu: [
                { role: 'reload' },
                { role: 'forceReload' },
                { role: 'toggleDevTools' },
                { type: 'separator' },
                { role: 'resetZoom' },
                { role: 'zoomIn' },
                { role: 'zoomOut' },
                { type: 'separator' },
                { role: 'togglefullscreen' }
            ]
        },
        {
            label: 'Tools',
            submenu: [
                {
                    label: 'Check Server Status',
                    click: () => {
                        checkServerStatus(mainWindow);
                    }
                },
                {
                    label: 'Clear Cache',
                    click: () => {
                        mainWindow.webContents.session.clearCache();
                        mainWindow.webContents.session.clearStorageData();
                        mainWindow.reload();
                    }
                },
                {
                    label: 'Server Information',
                    click: () => {
                        showServerInfo(mainWindow);
                    }
                }
            ]
        },
        {
            label: 'Help',
            submenu: [
                {
                    label: 'About',
                    click: () => {
                        mainWindow.webContents.executeJavaScript(`
                            alert('IT Service Request System v1.0\\n\\nServer: ${SERVER_URL}\\n\\n© 2026 IT Service Team');
                        `);
                    }
                },
                {
                    label: 'Troubleshooting',
                    click: () => {
                        mainWindow.webContents.executeJavaScript(`
                            alert('Troubleshooting:\\n\\n1. Check server is running\\n2. Check network connection\\n3. Ping server: ping ${SERVER_IP}\\n4. Contact IT Support: ext. 1234');
                        `);
                    }
                }
            ]
        }
    ];

    const menu = Menu.buildFromTemplate(template);
    Menu.setApplicationMenu(menu);
}

// Check server status
function checkServerStatus(window) {
    const http = require('http');
    const options = {
        hostname: SERVER_IP,
        port: 80,
        path: '/it-service-request',
        method: 'GET',
        timeout: 5000
    };

    const req = http.request(options, (res) => {
        window.webContents.executeJavaScript(`
            alert('Server Status: ONLINE\\nResponse Code: ${res.statusCode}\\nServer: ${SERVER_URL}');
        `);
    });

    req.on('error', (err) => {
        window.webContents.executeJavaScript(`
            alert('Server Status: OFFLINE\\nError: ${err.message}\\n\\nPlease check:\\n1. Server is turned on\\n2. Network connection\\n3. Server IP: ${SERVER_IP}');
        `);
    });

    req.on('timeout', () => {
        req.destroy();
        window.webContents.executeJavaScript(`
            alert('Server Status: TIMEOUT\\nServer is not responding\\n\\nPlease check server status and try again.');
        `);
    });

    req.end();
}

// Show server information
function showServerInfo(window) {
    window.webContents.executeJavaScript(`
        const info = \`
        <h3>Server Information</h3>
        <p><strong>Server URL:</strong> ${SERVER_URL}</p>
        <p><strong>Server IP:</strong> ${SERVER_IP}</p>
        <p><strong>Application:</strong> IT Service Request System</p>
        <p><strong>Version:</strong> 1.0.0</p>
        <p><strong>Status:</strong> Connected to server</p>
        <p><strong>Access Mode:</strong> Network Deployment</p>
        \`;
        alert(info);
    `);
}

// App event handlers
app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
        createWindow();
    }
});

// Security: Prevent new window creation
app.on('web-contents-created', (event, contents) => {
    contents.on('new-window', (event, navigationUrl) => {
        event.preventDefault();
        shell.openExternal(navigationUrl);
    });
});

// Handle app protocol for deep links (optional)
app.setAsDefaultProtocolClient('it-service-request');

// Add error handling for server connection
app.on('certificate-error', (event, webContents, url, error, certificate, callback) => {
    // For development, ignore certificate errors
    if (process.env.NODE_ENV === 'development') {
        event.preventDefault();
        callback(true);
    } else {
        callback(false);
    }
});

// Log application startup
console.log('IT Service Request Desktop Application starting...');
console.log(`Target server: ${SERVER_URL}`);
console.log(`Node.js version: ${process.version}`);
console.log(`Electron version: ${process.versions.electron}`);
console.log(`Platform: ${process.platform}`);
