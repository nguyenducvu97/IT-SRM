@echo off
echo Restarting Apache to apply .htaccess changes...
echo.

REM Stop Apache
"C:\xampp\apache\bin\httpd.exe" -k stop

REM Wait 3 seconds
timeout /t 3 /nobreak >nul

REM Start Apache
"C:\xampp\apache\bin\httpd.exe" -k start

REM Wait 2 seconds
timeout /t 2 /nobreak >nul

REM Check if Apache is running
"C:\xampp\apache\bin\httpd.exe" -k status

echo.
echo Apache restart completed!
echo.
echo Please test the application now.
pause
