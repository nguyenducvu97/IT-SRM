# PowerShell script to install IT Service Request Desktop App
# Run as Administrator

Write-Host "IT Service Request Desktop App Installer" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "Please run this script as Administrator!" -ForegroundColor Red
    Write-Host "Right-click the script and select 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Check Node.js installation
Write-Host "Checking Node.js installation..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Write-Host "Node.js is installed: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "Node.js is not installed!" -ForegroundColor Red
    Write-Host "Please download and install Node.js from: https://nodejs.org/" -ForegroundColor Yellow
    Write-Host "Choose the LTS version and restart this script after installation." -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Navigate to project directory
$projectPath = "C:\xampp\htdocs\it-service-request"
if (Test-Path $projectPath) {
    Set-Location $projectPath
    Write-Host "Changed to project directory: $projectPath" -ForegroundColor Green
} else {
    Write-Host "Project directory not found: $projectPath" -ForegroundColor Red
    Write-Host "Please make sure the IT Service Request project is installed in XAMPP." -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Install npm dependencies
Write-Host "Installing npm dependencies..." -ForegroundColor Yellow
try {
    npm install
    Write-Host "Dependencies installed successfully!" -ForegroundColor Green
} catch {
    Write-Host "Failed to install dependencies!" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit
}

# Check for icon file
$iconPath = "assets\icon.ico"
if (-NOT (Test-Path $iconPath)) {
    Write-Host "Warning: icon.ico not found in assets folder!" -ForegroundColor Yellow
    Write-Host "The app will use default icon. Consider converting logo-vuit-sgi-vina.png to icon.ico" -ForegroundColor Yellow
    Write-Host "You can use: https://convertio.co/png-ico/" -ForegroundColor Yellow
    Write-Host ""
}

# Build the application
Write-Host "Building desktop application..." -ForegroundColor Yellow
try {
    npm run build-win
    Write-Host "Build completed successfully!" -ForegroundColor Green
} catch {
    Write-Host "Build failed!" -ForegroundColor Red
    Write-Host "Please check the error messages above." -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Find the installer
$installerPath = Get-ChildItem -Path "dist" -Filter "*.exe" | Sort-Object LastWriteTime -Descending | Select-Object -First 1

if ($installerPath) {
    Write-Host "Installer created: $($installerPath.FullName)" -ForegroundColor Green
    Write-Host ""
    
    # Ask if user wants to install now
    $installNow = Read-Host "Do you want to install the application now? (y/n)"
    if ($installNow -eq "y" -or $installNow -eq "Y") {
        Write-Host "Starting installer..." -ForegroundColor Yellow
        Start-Process -FilePath $installerPath.FullName -Wait
        Write-Host "Installation completed!" -ForegroundColor Green
    }
    
    # Open dist folder
    Write-Host "Opening dist folder..." -ForegroundColor Yellow
    Start-Process "explorer.exe" -ArgumentList "dist"
} else {
    Write-Host "Installer not found in dist folder!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "Installation process completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Important notes:" -ForegroundColor Yellow
Write-Host "1. Make sure XAMPP (Apache + MySQL) is running before launching the app" -ForegroundColor White
Write-Host "2. The app will be available from Desktop shortcut and Start Menu" -ForegroundColor White
Write-Host "3. First launch might take a few seconds to load" -ForegroundColor White
Write-Host ""
Write-Host "If you encounter issues:" -ForegroundColor Yellow
Write-Host "- Check that Apache and MySQL are running in XAMPP Control Panel" -ForegroundColor White
Write-Host "- Verify that http://localhost/it-service-request works in browser" -ForegroundColor White
Write-Host "- Contact IT support for assistance" -ForegroundColor White
Write-Host ""

Read-Host "Press Enter to exit"
