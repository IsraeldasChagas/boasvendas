@echo off
chcp 65001 >nul
title Enviar boasvendas para GitHub
cd /d "%~dp0"

echo Executando enviarproGithub.ps1 ...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0enviarproGithub.ps1"
