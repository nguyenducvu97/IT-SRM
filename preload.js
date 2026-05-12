const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods that allow the renderer process to use
// the ipcRenderer without exposing the entire object
contextBridge.exposeInMainWorld('electronAPI', {
    // Platform info
    platform: process.platform,
    
    // App version
    version: process.env.npm_package_version || '1.0.0',
    
    // Minimize window
    minimizeWindow: () => ipcRenderer.invoke('minimize-window'),
    
    // Close window
    closeWindow: () => ipcRenderer.invoke('close-window'),
    
    // Get app info
    getAppInfo: () => ({
        name: 'IT Service Request',
        version: '1.0.0',
        platform: process.platform
    })
});

// Add desktop-specific styles to the web app
window.addEventListener('DOMContentLoaded', () => {
    // Add desktop app class to body for CSS targeting
    document.body.classList.add('electron-app');
    
    // Add desktop-specific CSS
    const style = document.createElement('style');
    style.textContent = `
        body.electron-app {
            /* Remove browser-specific elements */
        }
        
        body.electron-app .browser-only {
            display: none !important;
        }
        
        body.electron-app .desktop-only {
            display: block !important;
        }
        
        /* Add desktop app styling */
        .electron-app .app-header {
            -webkit-app-region: drag;
        }
        
        .electron-app .app-header button,
        .electron-app .app-header input,
        .electron-app .app-header a {
            -webkit-app-region: no-drag;
        }
    `;
    document.head.appendChild(style);
    
    // Add desktop app info to UI
    const addDesktopInfo = () => {
        const header = document.querySelector('.app-header, .header, nav');
        if (header && !header.querySelector('.desktop-info')) {
            const info = document.createElement('div');
            info.className = 'desktop-info desktop-only';
            info.innerHTML = `
                <span style="color: #666; font-size: 12px; margin-left: 10px;">
                    🖥️ Desktop App v${window.electronAPI?.version || '1.0.0'}
                </span>
            `;
            header.appendChild(info);
        }
    };
    
    // Try to add info immediately and retry if DOM not ready
    addDesktopInfo();
    setTimeout(addDesktopInfo, 1000);
});
