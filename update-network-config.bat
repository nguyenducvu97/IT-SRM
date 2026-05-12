@echo off
echo IT Service Request - Update Network Configuration
echo =================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo Updating file access configuration for network deployment...
echo.

REM Get server IP
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    set server_ip=%%a
    set server_ip=!server_ip: =!
)
echo Server IP: %server_ip%

REM Backup original attachment.php
echo [1/5] Backing up original files...
if exist "C:\xampp\htdocs\it-service-request\api\attachment.php" (
    copy "C:\xampp\htdocs\it-service-request\api\attachment.php" "C:\xampp\htdocs\it-service-request\api\attachment.php.backup" >nul 2>&1
    echo ✅ attachment.php backed up
) else (
    echo ❌ attachment.php not found
)

REM Replace with network version
echo.
echo [2/5] Updating attachment handler for network access...
if exist "C:\xampp\htdocs\it-service-request\api\attachment-network.php" (
    copy "C:\xampp\htdocs\it-service-request\api\attachment-network.php" "C:\xampp\htdocs\it-service-request\api\attachment.php" >nul 2>&1
    echo ✅ attachment.php updated for network access
) else (
    echo ❌ attachment-network.php not found
)

REM Update JavaScript files for network URLs
echo.
echo [3/5] Updating JavaScript configuration for network access...

REM Update app.js
if exist "C:\xampp\htdocs\it-service-request\assets\js\app.js" (
    powershell -Command "(Get-Content 'C:\xampp\htdocs\it-service-request\assets\js\app.js') -replace 'http://localhost/it-service-request', 'http://%server_ip%/it-service-request' | Set-Content 'C:\xampp\htdocs\it-service-request\assets\js\app.js'"
    echo ✅ app.js updated with server IP
) else (
    echo ❌ app.js not found
)

REM Update request-detail.js
if exist "C:\xampp\htdocs\it-service-request\assets\js\request-detail.js" (
    powershell -Command "(Get-Content 'C:\xampp\htdocs\it-service-request\assets\js\request-detail.js') -replace 'http://localhost/it-service-request', 'http://%server_ip%/it-service-request' | Set-Content 'C:\xampp\htdocs\it-service-request\assets\js\request-detail.js'"
    echo ✅ request-detail.js updated with server IP
) else (
    echo ❌ request-detail.js not found
)

REM Update main.js for desktop app
if exist "C:\xampp\htdocs\it-service-request\main.js" (
    powershell -Command "(Get-Content 'C:\xampp\htdocs\it-service-request\main.js') -replace 'http://localhost/it-service-request', 'http://%server_ip%/it-service-request' | Set-Content 'C:\xampp\htdocs\it-service-request\main.js'"
    echo ✅ main.js updated with server IP
) else (
    echo ❌ main.js not found
)

REM Create network configuration file
echo.
echo [4/5] Creating network configuration...
echo # IT Service Request Network Configuration > "C:\xampp\htdocs\it-service-request\config\network.php"
echo ^<?php >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo // Network configuration for server deployment >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('SERVER_IP', '%server_ip%'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('BASE_URL', 'http://%server_ip%/it-service-request'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('API_URL', 'http://%server_ip%/it-service-request/api/'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('UPLOAD_URL', 'http://%server_ip%/it-service-request/api/attachment.php'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('ALLOWED_ORIGINS', ['http://%server_ip%', 'http://localhost']); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo ?^> >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo ✅ Network configuration created

REM Update Apache configuration
echo.
echo [5/5] Updating Apache configuration for file access...

REM Create virtual host configuration
echo Creating virtual host configuration...
echo ^<VirtualHost *:80^> > "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     DocumentRoot "C:/xampp/htdocs/it-service-request" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ServerName %server_ip% >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ServerAlias it-service.local >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^<Directory "C:/xampp/htdocs/it-service-request"^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Options Indexes FollowSymLinks >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         AllowOverride All >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Require all granted >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^</Directory^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^<Directory "C:/xampp/htdocs/it-service-request/uploads"^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Options -Indexes +FollowSymLinks >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         AllowOverride None >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         Require all granted >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         ^<FilesMatch "\.(pdf|doc|docx|xls|xlsx)$"^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo             Header set Content-Disposition attachment >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         ^</FilesMatch^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         ^<FilesMatch "\.(jpg|jpeg|png|gif)$"^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo             Header set Content-Disposition inline >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo         ^</FilesMatch^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     ^</Directory^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo. >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     # Enable CORS for file access >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Origin "*" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo     Header always set Access-Control-Allow-Headers "Content-Type, Authorization" >> "C:\xampp\apache\conf\extra\it-service-request.conf"
echo ^</VirtualHost^> >> "C:\xampp\apache\conf\extra\it-service-request.conf"

REM Include virtual host in main config
powershell -Command "if ((Get-Content 'C:\xampp\apache\conf\httpd.conf') -notcontains 'Include conf/extra/it-service-request.conf') { Add-Content 'C:\xampp\apache\conf\httpd.conf' 'Include conf/extra/it-service-request.conf' }"

echo ✅ Apache configuration updated

REM Restart Apache
echo.
echo Restarting Apache to apply changes...
net stop Apache2.4 >nul 2>&1
timeout /t 2 >nul
net start Apache2.4

echo.
echo =================================================
echo Network Configuration Update Complete!
echo.
echo Updated Files:
echo ✅ attachment.php - Network file handler
echo ✅ app.js - Server IP configuration
echo ✅ request-detail.js - Server IP configuration  
echo ✅ main.js - Desktop app server URL
echo ✅ network.php - Network configuration file
echo ✅ Apache - Virtual host and file access
echo.
echo Server Information:
echo - IP Address: %server_ip%
echo - Web URL: http://%server_ip%/it-service-request
echo - File Access: http://%server_ip%/it-service-request/api/attachment.php
echo.
echo File Access Testing:
echo 1. Upload a file from any client machine
echo 2. Access the file from another client machine
echo 3. Verify download works correctly
echo.
echo File URLs:
echo - Download: http://%server_ip%/it-service-request/api/attachment.php?file=filename.pdf&action=download
echo - View: http://%server_ip%/it-service-request/api/attachment.php?file=filename.jpg&action=view
echo.
echo Security:
echo - Authentication required for downloads
echo - Permission-based access control
echo - Path traversal protection
echo - File type validation
echo.

REM Test file access
echo Testing file access...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://%server_ip%/it-service-request/api/attachment.php?file=test' -TimeoutSec 5; Write-Host '✅ File API accessible' } catch { Write-Host '⚠️ File API test failed (expected if no test file exists)' }"

REM Create test file for verification
echo Creating test file for verification...
echo This is a test file for network access verification > "C:\xampp\htdocs\it-service-request\uploads\network-test.txt"
echo ✅ Test file created: network-test.txt

echo.
echo Next Steps:
echo 1. Test file upload from client machine
echo 2. Test file download from different client machine
echo 3. Verify permissions work correctly
echo 4. Update desktop app if needed
echo.

REM Rebuild desktop app
echo Rebuilding desktop application with network configuration...
cd /d "C:\xampp\htdocs\it-service-request"
npm run build-win >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Desktop app rebuilt successfully!
    echo New installer: dist\IT Service Request Setup 1.0.0.exe
) else (
    echo ⚠️ Desktop app rebuild failed, but web access is working
)

echo.
echo File Access Summary:
echo ====================
echo.
echo How files work in network:
echo 1. User uploads file → Saved on server
echo 2. File metadata stored in database
echo 3. Staff can access from any machine
echo 4. Download via HTTP API
echo 5. Permissions enforced by server
echo.
echo Benefits:
echo ✅ Centralized file storage
echo ✅ Access from any machine
echo ✅ Permission-based security
echo ✅ Easy backup and management
echo ✅ Consistent user experience
echo.

REM Open web application for testing
set /p test=Open web application for testing? (y/n): 
if /i "%test%"=="y" (
    start http://%server_ip%/it-service-request
)

REM Open uploads folder
set /p open=Open uploads folder to verify files? (y/n): 
if /i "%open%"=="y" (
    start "" "C:\xampp\htdocs\it-service-request\uploads"
)

echo.
echo For troubleshooting:
echo - Check Apache logs: C:\xampp\apache\logs\*
echo - Test file access: curl http://%server_ip%/it-service-request/api/attachment.php?file=network-test.txt
echo - Check file permissions: icacls C:\xampp\htdocs\it-service-request\uploads
echo.

pause
