@echo off
rem Starts BRIX AI on http://localhost:8000 with PHP 8.3.
rem XAMPP keeps its own PHP 8.0 for your other projects.
set "PHP=%LOCALAPPDATA%\Programs\php-8.3\php.exe"
if not exist "%PHP%" (
  echo PHP 8.3 was not found at %PHP%
  exit /b 1
)
cd /d "%~dp0"
echo BRIX AI:  http://localhost:8000
echo Admin:    http://localhost:8000/admin
echo Press Ctrl+C to stop.
"%PHP%" -S localhost:8000 -t public public/index.php
