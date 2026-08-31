@echo off
:: Right-click this file and choose "Run as administrator"

echo ============================================================
echo  STEP 1: Kill stuck MySQL processes
echo ============================================================
taskkill /F /IM mysqld.exe
timeout /t 3 /nobreak >nul

netstat -ano | findstr ":3306" | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo ERROR: Port 3306 still in use.
    echo Open Task Manager ^> Details ^> End ALL mysqld.exe ^> Run this again.
    pause
    exit /b 1
)
echo Port 3306 is free.

echo.
echo ============================================================
echo  STEP 2: Run MySQL auth fix
echo ============================================================
call "%~dp0fix-xampp-mysql.bat
