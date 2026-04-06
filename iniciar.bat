@echo off
chcp 65001 >nul
title Vendaffacil — Enviar para GitHub
cd /d "%~dp0"

echo.
echo  Iniciando envio para o GitHub (enviarproGithub.ps1)...
echo  Pasta: %~dp0
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0enviarproGithub.ps1"
