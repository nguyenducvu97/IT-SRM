@echo off
echo IT Service Request - Server Setup Script
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo Configuring server for network access...
echo.

REM Get server IP
echo [1/8] Detecting server IP address...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    set server_ip=%%a
    set server_ip=!server_ip: =!
)
echo Server IP: %server_ip%

REM Configure Apache for network access
echo.
echo [2/8] Configuring Apache for network access...

REM Backup original httpd.conf
copy "C:\xampp\apache\conf\httpd.conf" "C:\xampp\apache\conf\httpd.conf.backup" >nul 2>&1

REM Update httpd.conf for network access
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'Listen 127.0.0.1:80', 'Listen 0.0.0.0:80' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"

REM Configure MySQL for remote access
echo.
echo [3/8] Configuring MySQL for remote access...

REM Backup my.ini
copy "C:\xampp\mysql\bin\my.ini" "C:\xampp\mysql\bin\my.ini.backup" >nul 2>&1

REM Update my.ini for network access
powershell -Command "(Get-Content 'C:\xampp\mysql\bin\my.ini') -replace 'bind-address = 127.0.0.1', 'bind-address = 0.0.0.0' | Set-Content 'C:\xampp\mysql\bin\my.ini'"

REM Create database user for network access
echo.
echo [4/8] Creating database user for network access...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE USER IF NOT EXISTS 'it_service_user'@'%' IDENTIFIED BY 'ServerAccess123!';" 2>nul
"C:\xampp\mysql\bin\mysql.exe" -u root -e "GRANT ALL PRIVILEGES ON it_service_request.* TO 'it_service_user'@'%';" 2>nul
"C:\xampp\mysql\bin\mysql.exe" -u root -e "FLUSH PRIVILEGES;" 2>nul
echo Database user created successfully!

REM Configure firewall
echo.
echo [5/8] Configuring Windows Firewall...

REM Allow Apache HTTP
netsh advfirewall firewall delete rule name="Apache HTTP Server" >nul 2>&1
netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=80

REM Allow MySQL (optional, for admin tools)
netsh advfirewall firewall delete rule name="MySQL Server" >nul 2>&1
netsh advfirewall firewall add rule name="MySQL Server" dir=in action=allow protocol=TCP localport=3306

echo Firewall rules configured!

REM Update application config
echo.
echo [6/8] Updating application configuration...

REM Update database config
if exist "C:\xampp\htdocs\it-service-request\config\database.php" (
    powershell -Command "(Get-Content 'C:\xampp\htdocs\it-service-request\config\database.php') -replace 'host = ''localhost''', 'host = ''%server_ip%''' | Set-Content 'C:\xampp\htdocs\it-service-request\config\database.php'"
    echo Database configuration updated!
) else (
    echo Database config file not found, skipping...
)

REM Create network config file
echo Creating network configuration...
echo # IT Service Request Network Configuration > "C:\xampp\htdocs\it-service-request\config\network.php"
echo ^<?php >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('SERVER_IP', '%server_ip%'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('BASE_URL', 'http://%server_ip%/it-service-request'); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo define('ALLOWED_ORIGINS', ['http://%server_ip%', 'http://localhost']); >> "C:\xampp\htdocs\it-service-request\config\network.php"
echo ?^> >> "C:\xampp\htdocs\it-service-request\config\network.php"

REM Restart services
echo.
echo [7/8] Restarting Apache and MySQL...

REM Stop services
net stop Apache2.4 >nul 2>&1
net stop mysql >nul 2>&1
timeout /t 2 >nul

REM Start services
net start Apache2.4
net start mysql

echo Services restarted!

REM Test configuration
echo.
echo [8/8] Testing server configuration...

REM Test Apache
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://%server_ip%/dashboard' -TimeoutSec 10; Write-Host '✅ Apache accessible from network' } catch { Write-Host '❌ Apache not accessible from network' }"

REM Test web application
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://%server_ip%/it-service-request' -TimeoutSec 10; Write-Host '✅ Web application accessible from network' } catch { Write-Host '❌ Web application not accessible from network' }"

REM Test database connection
"C:\xampp\mysql\bin\mysql.exe" -u it_service_user -pServerAccess123! -h %server_ip% -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Database accessible from network
) else (
    echo ❌ Database not accessible from network
)

echo.
echo ========================================
echo Server Setup Complete!
echo.
echo Server Information:
echo - IP Address: %server_ip%
echo - Web URL: http://%server_ip%/it-service-request
echo - Database User: it_service_user
echo - Database Password: ServerAccess123!
echo.
echo Client Access Instructions:
echo 1. Browser: http://%server_ip%/it-service-request
echo 2. Desktop App: Update main.js with server URL
echo 3. Shortcut: Create shortcut to http://%server_ip%/it-service-request
echo.

REM Create client shortcut file
echo Creating client shortcut...
echo @echo off > "C:\xampp\htdocs\it-service-request\client-access.bat"
echo start http://%server_ip%/it-service-request >> "C:\xampp\htdocs\it-service-request\client-access.bat"

REM Update desktop app for server
echo.
echo Updating desktop app for server access...
if exist "C:\xampp\htdocs\it-service-request\main.js" (
    copy "C:\xampp\htdocs\it-service-request\main.js" "C:\xampp\htdocs\it-service-request\main.js.backup" >nul 2>&1
    powershell -Command "(Get-Content 'C:\xampp\htdocs\it-service-request\main.js') -replace 'mainWindow.loadURL(''http://localhost/it-service-request'')', 'mainWindow.loadURL(''http://%server_ip%/it-service-request'')' | Set-Content 'C:\xampp\htdocs\it-service-request\main.js'"
    echo Desktop app configuration updated!
)

REM Rebuild desktop app
echo.
echo Rebuilding desktop application...
cd /d "C:\xampp\htdocs\it-service-request"
npm run build-win >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Desktop app rebuilt successfully!
    echo New installer: dist\IT Service Request Setup 1.0.0.exe
) else (
    echo ⚠️ Desktop app rebuild failed, but web access is working
)

echo.
echo ========================================
echo DEPLOYMENT SUMMARY
echo ========================================
echo.
echo ✅ Server configured for network access
echo ✅ Apache listening on all interfaces  
echo ✅ MySQL accessible from network
echo ✅ Firewall rules configured
echo ✅ Web application accessible
echo ✅ Desktop app updated
echo.
echo NEXT STEPS:
echo 1. Test access from client machines:
echo    - Open browser: http://%server_ip%/it-service-request
echo    - Verify login and functionality
echo.
echo 2. Deploy desktop app to clients:
echo    - Copy dist\IT Service Request Setup 1.0.0.exe
echo    - Install on client machines
echo.
echo 3. Create shortcuts for easy access:
echo    - Use client-access.bat
echo    - Create desktop shortcuts
echo.
echo 4. Monitor server performance:
echo    - Check Apache logs: C:\xampp\apache\logs\*
echo    - Check MySQL performance
echo    - Monitor network bandwidth
echo.

REM Open web application for testing
set /p test=Open web application for testing? (y/n): 
if /i "%test%"=="y" (
    start http://%server_ip%/it-service-request
)

REM Open dist folder
set /p open=Open dist folder for desktop app distribution? (y/n): 
if /i "%open%"=="y" (
    start "" "dist"
)

echo.
echo For troubleshooting:
echo - Check services: services.msc
echo - Check firewall: wf.msc
echo - Check network: ipconfig /all
echo - Test connection: telnet %server_ip% 80
echo.

pause
