@echo off
setlocal

REM Ajusta esta ruta si XAMPP esta instalado en otra carpeta.
set "PHP_EXE=C:\xampp\php\php.exe"

if not exist "%PHP_EXE%" (
    echo No se encuentra PHP en %PHP_EXE%.
    echo Edita PHP_EXE dentro de iniciar_worker.bat.
    pause
    exit /b 1
)

echo Comprobando PDO_ODBC en el PHP de consola...
"%PHP_EXE%" -r "exit(in_array('odbc', PDO::getAvailableDrivers(), true) ? 0 : 1);"
if errorlevel 1 (
    echo ERROR: el PHP de consola no tiene disponible el driver PDO_ODBC.
    echo Comprueba el php.ini usado por C:\xampp\php\php.exe y habilita pdo_odbc.
    pause
    exit /b 1
)
echo PDO_ODBC disponible.
echo.

"%PHP_EXE%" "%~dp0worker\pedido_worker.php"

pause
