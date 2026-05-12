@echo off
echo Building IT Service Request Desktop Application...
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed!
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

echo Node.js version:
node --version
echo.

REM Check if npm is available
npm --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: npm is not available!
    pause
    exit /b 1
)

echo npm version:
npm --version
echo.

REM Install dependencies
echo Installing dependencies...
npm install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install dependencies!
    pause
    exit /b 1
)
echo Dependencies installed successfully!
echo.

REM Check if icon exists
if not exist "assets\icon.ico" (
    echo WARNING: icon.ico not found in assets folder!
    echo Please convert logo-vuit-sgi-vina.png to icon.ico format
    echo You can use: https://convertio.co/png-ico/
    echo.
    echo Creating a simple placeholder icon...
    echo This is just a placeholder - please replace with proper icon
    echo.
)

REM Build the application
echo Building desktop application...
npm run build-win
if %errorlevel% neq 0 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)

echo.
echo ========================================
echo BUILD SUCCESSFUL!
echo ========================================
echo.
echo Installer location: dist\IT Service Request Setup *.exe
echo.
echo Next steps:
echo 1. Double-click the installer to install
echo 2. Follow the installation wizard
echo 3. Desktop shortcut will be created automatically
echo 4. Double-click the desktop icon to launch
echo.
echo Make sure XAMPP (Apache + MySQL) is running before launching!
echo.

REM Ask if user wants to open dist folder
set /p openFolder="Do you want to open the dist folder? (y/n): "
if /i "%openFolder%"=="y" (
    start "" "dist"
)

echo Done!
pause
