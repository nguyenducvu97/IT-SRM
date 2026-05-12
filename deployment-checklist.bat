@echo off
echo IT Service Request - Deployment Checklist
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

echo Checking system requirements...
echo.

REM Check XAMPP installation
echo [1/6] Checking XAMPP installation...
if exist "C:\xampp\apache\bin\httpd.exe" (
    echo ✅ XAMPP Apache found
) else (
    echo ❌ XAMPP Apache not found
    echo Please install XAMPP from https://www.apachefriends.org/
    pause
    exit /b 1
)

if exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo ✅ XAMPP MySQL found
) else (
    echo ❌ XAMPP MySQL not found
    pause
    exit /b 1
)

REM Check services
echo.
echo [2/6] Checking services...
sc query Apache2.4 >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Apache service exists
    for /f "tokens=3" %%a in ('sc query Apache2.4 ^| findstr "STATE"') do (
        if "%%a"=="RUNNING" (
            echo ✅ Apache is running
        ) else (
            echo ⚠️ Apache is not running
            echo Starting Apache...
            net start Apache2.4
        )
    )
) else (
    echo ❌ Apache service not found
)

sc query mysql >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ MySQL service exists
    for /f "tokens=3" %%a in ('sc query mysql ^| findstr "STATE"') do (
        if "%%a"=="RUNNING" (
            echo ✅ MySQL is running
        ) else (
            echo ⚠️ MySQL is not running
            echo Starting MySQL...
            net start mysql
        )
    )
) else (
    echo ❌ MySQL service not found
)

REM Check web files
echo.
echo [3/6] Checking web files...
if exist "C:\xampp\htdocs\it-service-request\index.html" (
    echo ✅ Web application files found
) else (
    echo ❌ Web application files not found
    echo Please copy IT Service Request files to C:\xampp\htdocs\it-service-request\
    pause
    exit /b 1
)

REM Check database
echo.
echo [4/6] Checking database...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE it_service_request;" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Database exists
) else (
    echo ❌ Database not found
    echo Please import database schema from database/*.sql files
    echo.
    echo To import:
    echo 1. Open phpMyAdmin (http://localhost/phpmyadmin)
    echo 2. Create database "it_service_request"
    echo 3. Import files from database/ folder
    pause
)

REM Check web app accessibility
echo.
echo [5/6] Checking web application...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost/it-service-request' -TimeoutSec 10; Write-Host '✅ Web application accessible' } catch { Write-Host '❌ Web application not accessible' }"

REM Check desktop app
echo.
echo [6/6] Checking desktop application...
if exist "C:\Program Files\IT Service Request\IT Service Request.exe" (
    echo ✅ Desktop application installed
) else if exist "%LOCALAPPDATA%\Programs\IT Service Request\IT Service Request.exe" (
    echo ✅ Desktop application installed (user scope)
) else (
    echo ⚠️ Desktop application not found
    echo Please run IT Service Request Setup 1.0.0.exe to install
)

echo.
echo ========================================
echo Deployment Check Complete!
echo.

REM Test database connection
echo Testing database connection...
echo.
powershell -Command "
try { 
    $conn = New-Object System.Data.SqlClient.SqlConnection;
    $conn.ConnectionString = 'Server=localhost;Database=it_service_request;Uid=root;Pwd=;';
    $conn.Open();
    $conn.Close();
    Write-Host '✅ Database connection successful';
} catch { 
    Write-Host '❌ Database connection failed';
    Write-Host 'Error:' $_.Exception.Message;
}
"

echo.
echo Next steps:
echo 1. Open http://localhost/it-service-request in browser
echo 2. Test login with admin account
echo 3. Launch desktop application from Start Menu
echo 4. Verify all features are working
echo.

pause
