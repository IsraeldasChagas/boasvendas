@echo off
chcp 65001 >nul
title Renomear c:\boasvendas para vendaffacil
cd /d c:\
if not exist "c:\boasvendas" (
  if exist "c:\vendaffacil" ( echo Ja existe c:\vendaffacil. ) else ( echo c:\boasvendas nao encontrada. )
  pause & exit /b 1
)
if exist "c:\vendaffacil" (
  echo ERRO: c:\vendaffacil ja existe.
  pause & exit /b 1
)
echo A renomear...
ren "boasvendas" "vendaffacil"
if errorlevel 1 (
  echo FALHOU. Feche o Cursor, VS Code e terminais que usem c:\boasvendas e volte a executar este ficheiro.
  echo Dica: copie este .bat para o Ambiente de Trabalho, feche tudo, duplo clique no Ambiente de Trabalho.
) else (
  echo OK: pasta agora e c:\vendaffacil
  echo No Cursor: File - Open Folder - c:\vendaffacil
)
pause
