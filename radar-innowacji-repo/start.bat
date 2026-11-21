@echo off
REM Radar Innowacji - uruchomienie lokalne (Windows)
chcp 65001 >nul
cd /d "%~dp0"
set PORT=8000
if not "%~1"=="" set PORT=%~1

where php >nul 2>nul
if errorlevel 1 (
  echo.
  echo Nie znaleziono PHP. Zainstaluj PHP 8.1+ i dodaj go do zmiennej PATH - instrukcja w README.md
  echo.
  pause
  exit /b 1
)

REM Jesli w php.ini nie sa wlaczone wymagane rozszerzenia, wlacz je tylko dla tego uruchomienia
set PHPFLAGS=
php -r "exit(extension_loaded('pdo_sqlite') && extension_loaded('mbstring') ? 0 : 1);"
if not errorlevel 1 goto run
for /f "delims=" %%i in ('where php') do (
  set "PHPEXE=%%i"
  goto found
)
:found
for %%i in ("%PHPEXE%") do set "PHPDIR=%%~dpi"
set PHPFLAGS=-d extension_dir="%PHPDIR%ext" -d extension=pdo_sqlite -d extension=mbstring -d extension=openssl

:run
php %PHPFLAGS% setup.php --port=%PORT%
if errorlevel 1 (
  pause
  exit /b 1
)
echo.
echo Serwer dziala: http://localhost:%PORT%   (zatrzymanie: Ctrl+C)
start "" "http://localhost:%PORT%"
php %PHPFLAGS% -S localhost:%PORT% dev-router.php
