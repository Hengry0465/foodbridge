@echo off
setlocal EnableExtensions

echo ============================================================
echo  FoodBridge - Fix XAMPP MySQL (run as Administrator)
echo ============================================================
echo.

:: --- Step 1: Stop all MySQL processes ---
echo [1/6] Stopping all MySQL processes...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 3 /nobreak >nul

netstat -ano | findstr ":3306" | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo.
    echo ERROR: Port 3306 is still in use.
    echo Open Task Manager ^(Ctrl+Shift+Esc^), Details tab,
    echo end ALL mysqld.exe processes, then run this script again.
    echo.
    pause
    exit /b 1
)
echo       Port 3306 is free.

:: --- Step 2: Enable skip-grant-tables temporarily ---
echo [2/6] Enabling temporary skip-grant-tables in my.ini...
findstr /C:"skip-grant-tables" "C:\xampp\mysql\bin\my.ini" >nul
if errorlevel 1 (
    powershell -NoProfile -Command "(Get-Content 'C:\xampp\mysql\bin\my.ini' -Raw) -replace '(\[mysqld\]\r?\n)', '$1skip-grant-tables`r`n' | Set-Content 'C:\xampp\mysql\bin\my.ini' -NoNewline"
)

:: --- Step 3: Start MySQL ---
echo [3/6] Starting MySQL...
call "C:\xampp\mysql_start.bat"
timeout /t 6 /nobreak >nul

:: --- Step 4: Fix root auth + create database (MariaDB 10.4+) ---
echo [4/6] Fixing root authentication and creating database...
"C:\xampp\mysql\bin\mysql.exe" -u root < "%~dp0..\database\xampp-mysql-fix.sql"
if errorlevel 1 (
    echo.
    echo ERROR: Could not connect to MySQL.
    echo Try: open XAMPP Control Panel, click Start on MySQL, then run this script again.
    pause
    exit /b 1
)

:: --- Step 5: Remove skip-grant-tables ---
echo [5/6] Removing skip-grant-tables from my.ini...
powershell -NoProfile -Command "(Get-Content 'C:\xampp\mysql\bin\my.ini' -Raw) -replace '\r?\nskip-grant-tables','' | Set-Content 'C:\xampp\mysql\bin\my.ini' -NoNewline"

:: --- Step 6: Restart MySQL cleanly ---
echo [6/6] Restarting MySQL with normal security...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 3 /nobreak >nul
call "C:\xampp\mysql_start.bat"
timeout /t 5 /nobreak >nul

"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT user, host, plugin FROM mysql.user WHERE user='root';"
if errorlevel 1 (
    echo.
    echo WARNING: MySQL started but login test failed.
    echo Check XAMPP Control Panel - MySQL should show green Running.
    pause
    exit /b 1
)

echo.
echo SUCCESS! MySQL is fixed. Database 'asignment1' is ready.
echo.
echo Now run in your project folder:
echo   cd C:\Users\sudde\Asignment1
echo   php artisan config:clear
echo   php artisan migrate:fresh --seed
echo   php artisan serve
echo.
pause
