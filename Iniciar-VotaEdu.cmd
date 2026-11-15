@echo off
chcp 65001 >nul
set "PATH=C:\Dev\php;%PATH%"
cd /d "%~dp0"
echo ============================================================
echo  Iniciando VotaEdu en modo distribuido (Red local y Wi-Fi)
echo ============================================================
echo Se abrira un codigo QR para que puedas entrar desde cualquier telefono.
echo Para detener el servidor pulsa Ctrl+C.

"C:\Dev\php\php.exe" -c "C:\Dev\php\php.ini" artisan votaedu --port=8000
pause
