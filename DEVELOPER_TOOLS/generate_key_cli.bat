@echo off
title Tajer License Generator
cd /d "%~dp0"

set "PHP_EXE=..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php.exe"

echo ===================================================
echo       Tajer License Generator (Quick CLI)
echo ===================================================
echo.
set /p HID="1. Enter Hardware ID: "
echo.
echo Select License Type:
echo [1] Lifetime
echo [2] 365 Days (1 Year)
echo [3] 30 Days Trial
echo.
set /p OPT="Select (1, 2, or 3) [Default 1]: "

if "%OPT%"=="1" set LIC_TYPE=lifetime
if "%OPT%"=="2" set LIC_TYPE=365
if "%OPT%"=="3" set LIC_TYPE=30
if not defined LIC_TYPE set LIC_TYPE=lifetime

"%PHP_EXE%" -r "require_once 'keygen_cli.php'; KeygenCLI::run('%HID%', '%LIC_TYPE%');"
echo.
pause
