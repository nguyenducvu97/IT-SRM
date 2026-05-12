@echo off
echo IT Service Request Desktop App Installer
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Requesting administrator privileges...
    powershell -Command "Start-Process cmd -ArgumentList '/c cd /d %~dp0 && install-desktop-easy.bat' -Verb RunAs"
    exit
)

echo Running with administrator privileges...
echo.

REM Check Node.js
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed!
    echo Please download and install from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo Node.js is installed
node --version
echo.

REM Install dependencies
echo Installing npm dependencies...
npm install
if %errorlevel% neq 0 (
    echo ERROR: npm install failed!
    pause
    exit /b 1
)
echo Dependencies installed!
echo.

REM Build application
echo Building desktop application...
npm run build-win
if %errorlevel% neq 0 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)
echo Build completed!
echo.

REM Find installer
if exist "dist\*.exe" (
    echo Installer created successfully!
    echo Location: dist\*.exe
    echo.
    set /p install=Do you want to install now? (y/n): 
    if /i "%install%"=="y" (
        echo Starting installer...
        for %%f in (dist\*.exe) do (
            start "" "%%f"
            goto :installer_started
        )
        :installer_started
        echo Installer launched!
    )
    echo.
    echo Opening dist folder...
    start "" "dist"
) else (
    echo ERROR: No installer found in dist folder!
)

echo.
echo ========================================
echo Process completed!
echo.
echo Important:
echo 1. Make sure XAMPP is running before launching app
echo 2. Desktop shortcut will be created after installation
echo 3. First launch may take a few seconds
echo.

pause
