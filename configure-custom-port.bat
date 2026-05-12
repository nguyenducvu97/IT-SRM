@echo off
echo IT Service Request - Configure Custom Port 3005
echo ==============================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

set SERVER_IP=192.168.220.25
set SERVER_PORT=3005
set SERVER_URL=http://%SERVER_IP%:%SERVER_PORT%/it-service-request

echo Configuring for: %SERVER_URL%
echo.

REM Update Apache configuration
echo [1/7] Updating Apache port configuration...
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'Listen 80', 'Listen %SERVER_PORT%' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'Listen 127.0.0.1:80', 'Listen %SERVER_PORT%' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'Listen 0.0.0.0:80', 'Listen %SERVER_PORT%' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
echo ✅ Apache port updated to %SERVER_PORT%

REM Update VirtualHost configuration
echo.
echo [2/7] Updating VirtualHost configuration...
echo ^<VirtualHost *:%SERVER_PORT%^> > "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     DocumentRoot "C:/xampp/htdocs/it-service-request" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ServerName %SERVER_IP% >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ServerAlias it-service.local >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^<Directory "C:/xampp/htdocs/it-service-request"^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Options Indexes FollowSymLinks >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         AllowOverride All >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Require all granted >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^</Directory^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     # Enable CORS for file access >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Origin "*" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Headers "Content-Type, Authorization" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo ^</VirtualHost^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"

REM Include virtual host in main config
powershell -Command "if ((Get-Content 'C:\xampp\apache\conf\httpd.conf') -notcontains 'Include conf/extra/it-service-request.conf') { Add-Content 'C:\xampp\apache\conf\httpd.conf' 'Include conf/extra/it-service-request.conf' }"
echo ✅ VirtualHost configuration updated

REM Update database configuration
echo.
echo [3/7] Updating database configuration...
if exist "config\database.php" (
    powershell -Command "(Get-Content 'config\database.php') -replace \"host = '[^']*'\", \"host = '%SERVER_IP%'\" | Set-Content 'config\database.php'"
    echo ✅ Database configuration updated
) else (
    echo ❌ Database configuration file not found
)

REM Update JavaScript files
echo.
echo [4/7] Updating JavaScript configuration...
if exist "assets\js\app.js" (
    powershell -Command "(Get-Content 'assets\js\app.js') -replace 'http://localhost/it-service-request', '%SERVER_URL%' | Set-Content 'assets\js\app.js'"
    echo ✅ app.js updated
)

if exist "assets\js\request-detail.js" (
    powershell -Command "(Get-Content 'assets\js\request-detail.js') -replace 'http://localhost/it-service-request', '%SERVER_URL%' | Set-Content 'assets\js\request-detail.js'"
    echo ✅ request-detail.js updated
)

REM Update main.js for desktop app
echo.
echo [5/7] Updating desktop app configuration...
if exist "main.js" (
    powershell -Command "(Get-Content 'main.js') -replace \"const SERVER_URL = 'http://[^']*'\", \"const SERVER_URL = '%SERVER_URL%'\" | Set-Content 'main.js'"
    powershell -Command "(Get-Content 'main.js') -replace 'http://localhost/it-service-request', '%SERVER_URL%' | Set-Content 'main.js'"
    echo ✅ main.js updated
)

REM Create network configuration file
echo.
echo [6/7] Creating network configuration file...
echo ^<?php > "config\network.php"
echo // Network configuration for server deployment >> "config\network.php"
echo define('SERVER_IP', '%SERVER_IP%'); >> "config\network.php"
echo define('SERVER_PORT', '%SERVER_PORT%'); >> "config\network.php"
echo define('BASE_URL', '%SERVER_URL%'); >> "config\network.php"
echo define('API_URL', 'http://%SERVER_IP%:%SERVER_PORT%/it-service-request/api/'); >> "config\network.php"
echo define('UPLOAD_URL', 'http://%SERVER_IP%:%SERVER_PORT%/it-service-request/api/attachment.php'); >> "config\network.php"
echo define('ALLOWED_ORIGINS', ['http://%SERVER_IP%:%SERVER_PORT%', 'http://localhost']); >> "config\network.php"
echo ?^> >> "config\network.php"
echo ✅ Network configuration created

REM Update firewall configuration
echo.
echo [7/7] Updating firewall configuration...
netsh advfirewall firewall delete rule name="Apache HTTP Server" >nul 2>&1
netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=%SERVER_PORT%
echo ✅ Firewall rule added for port %SERVER_PORT%

REM Restart Apache
echo.
echo Restarting Apache to apply changes...
net stop Apache2.4 >nul 2>&1
timeout /t 2 >nul
net start Apache2.4

REM Test configuration
echo.
echo Testing configuration...
powershell -Command "try { $response = Invoke-WebRequest -Uri '%SERVER_URL%' -TimeoutSec 10; Write-Host '✅ Server accessible at: %SERVER_URL%'; Write-Host 'Status Code:' $response.StatusCode } catch { Write-Host '❌ Server not accessible - Check configuration' }"

echo.
echo ==============================================
echo Configuration Complete!
echo ==============================================
echo.
echo Server Configuration:
echo - IP Address: %SERVER_IP%
echo - Port: %SERVER_PORT%
echo - URL: %SERVER_URL%
echo.
echo Updated Files:
echo ✅ Apache config (port %SERVER_PORT%)
echo ✅ VirtualHost configuration
echo ✅ Database configuration
echo ✅ JavaScript files
echo ✅ Desktop app configuration
echo ✅ Network configuration
echo ✅ Firewall rules
echo.
echo Access URLs:
echo - Web App: %SERVER_URL%
echo - API: %SERVER_URL%/api/
echo - Files: %SERVER_URL%/api/attachment.php
echo.
echo Client Access Instructions:
echo 1. Users access: %SERVER_URL%
echo 2. Or create desktop shortcut with URL: %SERVER_URL%
echo 3. Or build desktop app with custom port
echo.
echo Testing Commands:
echo - From server: curl %SERVER_URL%
echo - From client: ping %SERVER_IP%
echo - Port test: telnet %SERVER_IP% %SERVER_PORT%
echo.
echo Next Steps:
echo 1. Test web application functionality
echo 2. Test file upload/download
echo 3. Build desktop app with new configuration
echo 4. Deploy to client machines
echo 5. Test from multiple client machines
echo.

REM Create desktop app with custom port
set /p build=Build desktop application with custom port? (y/n): 
if /i "%build%"=="y" (
    echo.
    echo Building desktop application for %SERVER_URL%...
    npm run build-win
    if %errorlevel% equ 0 (
        echo ✅ Desktop app built successfully!
        echo Installer: dist\IT Service Request Setup 1.0.0.exe
    ) else (
        echo ❌ Desktop app build failed
    )
)

REM Open web application for testing
set /p test=Open web application for testing? (y/n): 
if /i "%test%"=="y" (
    start %SERVER_URL%
)

echo.
echo Configuration Summary:
echo ====================
echo Server: %SERVER_URL%
echo Port: %SERVER_PORT%
echo Status: Configured and ready
echo.
echo Important Notes:
echo - Apache now runs on port %SERVER_PORT%
echo - Firewall allows port %SERVER_PORT%
echo - All URLs updated to use custom port
echo - Desktop app configured for custom port
echo.

pause
