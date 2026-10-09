@echo off
chcp 65001 >nul
title مولّد تراخيص برنامج تاجر - خاص بالمطوّر
echo ========================================================
echo       أداة توليد التراخيص لبرنامج تاجر (المطوّر فقط)
echo ========================================================
echo.

:: الانتقال لمجلد السكريبت لتفادي مشاكل المسارات ذات المسافات أو الشرطة المائلة
cd /d "%~dp0"

:: البحث عن محرك PHP المدمج مع البرنامج أو في النظام
set "PHP_EXE="
if exist "..\php\php.exe" set "PHP_EXE=..\php\php.exe"
if not defined PHP_EXE if exist "php\php.exe" set "PHP_EXE=php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE where php >nul 2>nul && set "PHP_EXE=php"

if not defined PHP_EXE (
    echo [خطأ] لم يتم العثور على محرك PHP لتشغيل السيرفر!
    echo تأكد من وجود مجلد php بجانب مجلد DEVELOPER_TOOLS أو تثبيت PHP.
    pause
    exit /b 1
)

echo تم العثور على محرك PHP بنجاح.
echo جاري تشغيل خادم التراخيص على: http://127.0.0.1:8989/keygen.php
echo.
echo ========================================================
echo نافذة السيرفر تعمل الآن... اضغط Ctrl+C لإيقافها بعد الانتهاء.
echo ========================================================

:: فتح المتصفح بعد ثانية واحدة لضمان بدء السيرفر بنجاح
start /b "" cmd /c "timeout /t 1 /nobreak >nul & start http://127.0.0.1:8989/keygen.php"

:: تشغيل خادم PHP المحلي
"%PHP_EXE%" -S 127.0.0.1:8989 -t .
pause
