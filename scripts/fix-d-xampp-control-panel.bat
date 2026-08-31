@echo off
:: Run as Administrator (right-click -> Run as administrator)
:: Stops XAMPP from warning about port 3306 when MySQL uses 3308

set INI=D:\xampp\xampp-control.ini

if not exist "%INI%" (
    echo ERROR: %INI% not found
    pause
    exit /b 1
)

taskkill /F /IM xampp-control.exe >nul 2>&1
timeout /t 2 /nobreak >nul

> "%INI%" (
echo [Common]
echo Edition=
echo Editor=notepad.exe
echo Browser=
echo Debug=0
echo Debuglevel=0
echo TomcatVisible=1
echo Language=en
echo.
echo [EnableModules]
echo Apache=1
echo MySQL=1
echo FileZilla=1
echo Mercury=1
echo Tomcat=1
echo.
echo [Checks]
echo CheckDefaultPorts=1
echo.
echo [ServicePorts]
echo MySQL=3308
echo Apache=80
echo ApacheSSL=443
echo.
echo [ServiceNames]
echo MySQL=mysql
echo.
echo [BinaryNames]
echo MySQL=mysqld.exe
)

echo Updated %INI% - MySQL port check is now 3308.
echo Re-open D:\xampp\xampp-control.exe and restart MySQL.
pause
