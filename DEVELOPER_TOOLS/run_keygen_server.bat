@echo off
chcp 65001 >nul
title مولّد تراخيص برنامج تاجر - خاص بالمطوّر
echo ========================================================
echo       أداة توليد التراخيص لبرنامج تاجر (المطوّر فقط)
echo ========================================================
echo.

:: البحث عن محرك PHP المدمج مع البرنامج أو في النظام
set "PHP_EXE="
if exist "%~dp0..\php\php.exe" set "PHP_EXE=%~dp0..\php\php.exe"
if not defined PHP_EXE if exist "%~dp0php\php.exe" set "PHP_EXE=%~dp0php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE where php >nul 2>nul && set "PHP_EXE=php"

if not defined PHP_EXE (
    echo [خطأ] لم يتم العثور على محرك PHP لتشغيل السيرفر!
    echo تأكد من وجود مجلد php بجانب مجلد DEVELOPER_TOOLS أو تثبيت PHP.
    pause
    exit /b 1
)

echo تم العثور على PHP: %PHP_EXE%
echo جاري تشغيل الأداة وفتح المتصفح على: http://localhost:8989/keygen.php ...
start "" "http://localhost:8989/keygen.php"
"%PHP_EXE%" -S localhost:8989 -t "%~dp0"
pause
