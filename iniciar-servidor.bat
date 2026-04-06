@echo off
chcp 65001 >nul
title Vendaffacil — servidor local
cd /d "%~dp0"

echo.
echo  Vendaffacil — iniciando servidor Laravel...
echo  Pasta: %CD%
echo.

where php >nul 2>&1
if errorlevel 1 (
    echo [ERRO] PHP nao encontrado no PATH. Instale o PHP ou adicione-o ao PATH.
    pause
    exit /b 1
)

php artisan serve --host=127.0.0.1 --port=8000

echo.
echo  Servidor encerrado.
pause
