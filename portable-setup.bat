@echo off
echo IT Service Request - Portable Setup Script
echo ========================================
echo.
echo This script will help set up IT Service Request on a new computer
echo.

REM Check if XAMPP is installed
echo Checking XAMPP installation...
if not exist "C:\xampp" (
    echo ❌ XAMPP not found in C:\xampp
    echo.
    echo Please install XAMPP first:
    echo 1. Download from: https://www.apachefriends.org/download.html
    echo 2. Run installer with default settings
    echo 3. Start Apache and MySQL from XAMPP Control Panel
    echo.
    echo After installing XAMPP, run this script again.
    pause
    exit /b 1
)

echo ✅ XAMPP found
echo.

REM Start services
echo Starting Apache and MySQL services...
cd /d C:\xampp

start /b apache\bin\httpd.exe -f apache\conf\httpd.conf -k start
timeout /t 2 >nul

start /b mysql\bin\mysqld.exe --defaults-file=mysql\bin\my.ini
timeout /t 3 >nul

echo ✅ Services started
echo.

REM Check if web files exist
if not exist "htdocs\it-service-request" (
    echo ❌ Web application not found
    echo.
    echo Please copy the IT Service Request web files to:
    echo C:\xampp\htdocs\it-service-request\
    echo.
    pause
    exit /b 1
)

echo ✅ Web application found
echo.

REM Create database if not exists
echo Setting up database...
mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS it_service_request;" 2>nul
if %errorlevel% equ 0 (
    echo ✅ Database created/verified
) else (
    echo ❌ Failed to create database
    echo Please check MySQL service and credentials
    pause
    exit /b 1
)

REM Import database schema if needed
echo Checking database tables...
mysql\bin\mysql.exe -u root it_service_request -e "SHOW TABLES;" 2>nul | findstr "service_requests" >nul
if %errorlevel% neq 0 (
    echo Importing database schema...
    if exist "htdocs\it-service-request\database\schema.sql" (
        mysql\bin\mysql.exe -u root it_service_request < htdocs\it-service-request\database\schema.sql
        echo ✅ Database schema imported
    ) else (
        echo ⚠️ Database schema file not found
        echo Please import manually from phpMyAdmin
    )
) else (
    echo ✅ Database tables already exist
)

REM Set permissions
echo Setting file permissions...
icacls htdocs\it-service-request\uploads /grant Everyone:F /T >nul 2>&1
icacls htdocs\it-service-request\logs /grant Everyone:F /T >nul 2>&1
icacls htdocs\it-service-request\background_jobs /grant Everyone:F /T >nul 2>&1
echo ✅ Permissions set

REM Test web application
echo Testing web application...
powershell -Command "
try {
    $response = Invoke-WebRequest -Uri 'http://localhost/it-service-request' -TimeoutSec 10;
    if ($response.StatusCode -eq 200) {
        Write-Host '✅ Web application is accessible';
    } else {
        Write-Host '⚠️ Web application returned status code:' $response.StatusCode;
    }
} catch {
    Write-Host '❌ Web application not accessible';
    Write-Host 'Please check Apache configuration and file permissions';
}
"

echo.
echo ========================================
echo Setup Complete!
echo.
echo Web Application: http://localhost/it-service-request
echo.
echo Next steps:
echo 1. Test the web application in your browser
echo 2. Run IT Service Request Setup 1.0.0.exe to install desktop app
echo 3. Create user accounts and configure system
echo.

REM Ask to open browser
set /p open=Open web application now? (y/n): 
if /i "%open%"=="y" (
    start http://localhost/it-service-request
)

REM Ask to install desktop app
if exist "htdocs\it-service-request\dist\IT Service Request Setup 1.0.0.exe" (
    set /p install=Install desktop application now? (y/n): 
    if /i "%install%"=="y" (
        echo Starting desktop application installer...
        start "" "htdocs\it-service-request\dist\IT Service Request Setup 1.0.0.exe"
    )
)

echo.
echo For troubleshooting, check:
echo - XAMPP Control Panel: C:\xampp\xampp-control.exe
echo - Apache logs: C:\xampp\apache\logs\error.log
echo - MySQL logs: C:\xampp\mysql\data\mysql.err
echo - Application logs: C:\xampp\htdocs\it-service-request\logs\
echo.

pause
