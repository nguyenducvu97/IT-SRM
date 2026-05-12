@echo off
echo IT Service Request - Network Test Script
echo ========================================
echo.

REM Get server IP (you can change this)
set SERVER_IP=192.168.1.100

echo Testing network connectivity to server: %SERVER_IP%
echo.

REM Test 1: Ping server
echo [1/5] Testing ping to server...
ping -n 4 %SERVER_IP%
if %errorlevel% equ 0 (
    echo ✅ Server is reachable via ping
) else (
    echo ❌ Server is not reachable via ping
    echo Please check:
    echo - Server is turned on
    echo - Network cable is connected
    echo - IP address is correct
)
echo.

REM Test 2: Test HTTP connection
echo [2/5] Testing HTTP connection...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://%SERVER_IP%/dashboard' -TimeoutSec 10; Write-Host '✅ Apache server is accessible (HTTP)'; Write-Host 'Status Code:' $response.StatusCode } catch { Write-Host '❌ Apache server is not accessible'; Write-Host 'Error:' $_.Exception.Message }"
echo.

REM Test 3: Test application
echo [3/5] Testing IT Service Request application...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://%SERVER_IP%/it-service-request' -TimeoutSec 10; Write-Host '✅ IT Service Request is accessible'; Write-Host 'Status Code:' $response.StatusCode } catch { Write-Host '❌ IT Service Request is not accessible'; Write-Host 'Error:' $_.Exception.Message }"
echo.

REM Test 4: Test MySQL connection (if port 3306 is open)
echo [4/5] Testing MySQL connection...
telnet %SERVER_IP% 3306
echo Note: If connection succeeds, you'll see MySQL welcome message
echo If it fails, MySQL port may be blocked or not running
echo.

REM Test 5: Test DNS resolution
echo [5/5] Testing DNS resolution...
nslookup %SERVER_IP%
if %errorlevel% equ 0 (
    echo ✅ DNS resolution successful
) else (
    echo ❌ DNS resolution failed
)
echo.

echo ========================================
echo Network Test Complete!
echo.
echo Summary:
echo - Server IP: %SERVER_IP%
echo - Ping Test: Check if server is online
echo - HTTP Test: Check if Apache is running
echo - App Test: Check if web app is accessible
echo - MySQL Test: Check if database port is open
echo - DNS Test: Check if name resolution works
echo.

echo Troubleshooting Tips:
echo 1. If ping fails: Check server power and network
echo 2. If HTTP fails: Check Apache service and firewall
echo 3. If App fails: Check web files and configuration
echo 4. If MySQL fails: Check MySQL service and permissions
echo 5. If DNS fails: Check DNS server configuration
echo.

echo Next Steps:
echo 1. Fix any failed tests above
echo 2. Run server-setup.bat on server machine
echo 3. Test from client machines
echo 4. Deploy desktop application
echo.

pause
