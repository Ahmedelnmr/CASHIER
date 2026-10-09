@echo off
title Tajer License Generator
cd /d "%~dp0"

set "PHP_EXE=..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php.exe"

echo Starting License Server on http://127.0.0.1:8989/keygen.php ...
start "" "http://127.0.0.1:8989/keygen.php"
"%PHP_EXE%" -S 127.0.0.1:8989 -t .
pause
