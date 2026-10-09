@echo off
chcp 65001 >nul
title مولّد التراخيص السريع - كونسول
echo ===================================================
echo     مولّد تراخيص برنامج تاجر (Hardware Lock)
echo ===================================================
echo.
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

php -r "require_once '%~dp0keygen_cli.php'; KeygenCLI::run('%HID%', '%LIC_TYPE%');"
echo.
pause
