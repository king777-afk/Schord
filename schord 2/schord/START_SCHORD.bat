@echo off
REM SCHoRD Quick Start Script

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║     📋 SCHoRD - School Health ^& Record Database 📋         ║
echo ║              Quick Start Setup                               ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

echo [1/3] Checking XAMPP installation...
if not exist "C:\xampp\apache_start.bat" (
    echo ❌ XAMPP not found! Please install XAMPP first.
    pause
    exit /b 1
)
echo ✅ XAMPP found!

echo.
echo [2/3] Starting services...
echo.
echo Starting Apache...
start "" cmd /c c:\xampp\apache_start.bat
timeout /t 2 /nobreak

echo.
echo Starting MySQL...
start "" cmd /c c:\xampp\mysql_start.bat
timeout /t 3 /nobreak

echo.
echo ✅ Services started!
echo.

echo [3/3] Opening SCHoRD in browser...
echo.

echo Waiting for services to fully start...
timeout /t 2 /nobreak

echo Opening browser...
start "" "http://localhost/schord/check_db.php"

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                   ✅ Setup Complete!                         ║
echo ║                                                              ║
echo ║  📌 Browser should open automatically                       ║
echo ║  🔗 If not, visit: http://localhost/schord/                ║
echo ║                                                              ║
echo ║  📝 Login Credentials:                                      ║
echo ║     Email: admin@schord.com                                ║
echo ║     Password: admin123                                     ║
echo ║                                                              ║
echo ║  ⚠️  Keep this window open while using SCHoRD              ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

pause\n