// IT Service Request - Client Configuration for Server Access
// This file should be used to configure desktop app for server deployment

// Server Configuration
const SERVER_CONFIG = {
    // Change this to your server IP
    SERVER_IP: '192.168.1.100', // Update this with your actual server IP
    
    // Application URLs
    BASE_URL: 'http://192.168.1.100/it-service-request',
    API_URL: 'http://192.168.1.100/it-service-request/api/',
    
    // Connection settings
    TIMEOUT: 30000, // 30 seconds timeout
    RETRY_ATTEMPTS: 3,
    RETRY_DELAY: 2000, // 2 seconds
    
    // Features
    ENABLE_OFFLINE_MODE: true,
    ENABLE_CACHE: true,
    ENABLE_AUTO_REFRESH: true,
    AUTO_REFRESH_INTERVAL: 30000 // 30 seconds
};

// Update main.js for server deployment
function updateMainForServer(serverIP) {
    const mainJsContent = `
const { app, BrowserWindow, Menu, shell, ipcMain } = require('electron');
const path = require('path');

// Server configuration
const SERVER_URL = 'http://${serverIP}/it-service-request';

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
        show: false,
        titleBarStyle: 'default'
    });

    // Load from server
    mainWindow.loadURL(SERVER_URL);

    // Show window when ready-to-show
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        
        // Open DevTools in development
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
                }
            ]
        },
        {
            label: 'Help',
            submenu: [
                {
                    label: 'Server Information',
                    click: () => {
                        showServerInfo(mainWindow);
                    }
                },
                {
                    label: 'About',
                    click: () => {
                        mainWindow.webContents.executeJavaScript('alert("IT Service Request System v1.0\\n\\nServer: ${SERVER_URL}\\n\\n© 2026 IT Service Team")');
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
        hostname: '${serverIP}',
        port: 80,
        path: '/it-service-request',
        method: 'GET',
        timeout: 5000
    };

    const req = http.request(options, (res) => {
        window.webContents.executeJavaScript('alert("Server Status: ONLINE\\nResponse Code: ' + res.statusCode + '")');
    });

    req.on('error', (err) => {
        window.webContents.executeJavaScript('alert("Server Status: OFFLINE\\nError: ' + err.message + '")');
    });

    req.on('timeout', () => {
        req.destroy();
        window.webContents.executeJavaScript('alert("Server Status: TIMEOUT\\nServer is not responding")');
    });

    req.end();
}

// Show server information
function showServerInfo(window) {
    window.webContents.executeJavaScript(\`
        const info = \`
        <h3>Server Information</h3>
        <p><strong>Server URL:</strong> ${SERVER_URL}</p>
        <p><strong>Server IP:</strong> ${serverIP}</p>
        <p><strong>Application:</strong> IT Service Request System</p>
        <p><strong>Version:</strong> 1.0.0</p>
        <p><strong>Status:</strong> Connected</p>
        \`;
        alert(info);
    \`);
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
`;

    return mainJsContent;
}

// Export for use in build scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        SERVER_CONFIG,
        updateMainForServer
    };
}

// Browser usage
if (typeof window !== 'undefined') {
    window.SERVER_CONFIG = SERVER_CONFIG;
}
