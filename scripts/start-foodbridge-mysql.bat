@echo off
:: Start FoodBridge MySQL (C:\xampp on port 3307)
:: Use this instead of D:\xampp if you also run RO server on port 3306.

set MYSQL_BIN=C:\xampp\mysql\bin
set MY_INI=C:\xampp\mysql\bin\my.ini

tasklist /FI "IMAGENAME eq mysqld.exe" /FI "WINDOWTITLE eq *" >nul 2>&1
netstat -ano | findstr ":3307" | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo FoodBridge MySQL is already running on port 3307.
    goto :done
)

echo Starting FoodBridge MySQL on port 3307...
start "" /B "%MYSQL_BIN%\mysqld.exe" --defaults-file="%MY_INI%"
timeout /t 5 /nobreak >nul

netstat -ano | findstr ":3307" | findstr "LISTENING" >nul
if errorlevel 1 (
    echo ERROR: Could not start MySQL on port 3307.
    echo Close D:\xampp Control Panel and try again, or open C:\xampp Control Panel.
    exit /b 1
)

:done
echo Ready. Run: php artisan serve
echo Admin login: admin@foodbridge.test / password
