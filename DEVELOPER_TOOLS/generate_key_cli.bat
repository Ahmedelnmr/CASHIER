@echo off
chcp 65001 >nul
title مولّد التراخيص السريع - كونسول
echo ===================================================
echo     مولّد تراخيص برنامج تاجر (Hardware Lock)
echo ===================================================
echo.

cd /d "%~dp0"

set "PHP_EXE="
if exist "..\php\php.exe" set "PHP_EXE=..\php\php.exe"
if not defined PHP_EXE if exist "php\php.exe" set "PHP_EXE=php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE where php >nul 2>nul && set "PHP_EXE=php"

if not defined PHP_EXE (
    echo [خطأ] لم يتم العثور على محرك PHP لتشغيل الأداة!
    pause
    exit /b 1
)

set /p HID="1. أدخل كود الجهاز (Hardware ID): "
echo.
echo اختر نوع الترخيص:
echo [1] دائم مدى الحياة (Lifetime)
echo [2] سنة كاملة (365 يوم)
echo [3] شهر تجريبي (30 يوم)
echo.
set /p OPT="اختر (1 أو 2 أو 3): "

if "%OPT%"=="1" set LIC_TYPE=lifetime
if "%OPT%"=="2" set LIC_TYPE=365
if "%OPT%"=="3" set LIC_TYPE=30
if not defined LIC_TYPE set LIC_TYPE=lifetime

"%PHP_EXE%" -r "require_once 'keygen_cli.php'; KeygenCLI::run('%HID%', '%LIC_TYPE%');"
echo.
pause
