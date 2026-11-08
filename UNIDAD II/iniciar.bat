@echo off
rem Lanzador Windows. Cada computadora crea su entorno virtual local.
setlocal
cd /d "%~dp0"
where py >nul 2>nul
if not errorlevel 1 (
  py -3 iniciar.py %*
) else (
  where python >nul 2>nul
  if errorlevel 1 (
    echo Falta Python 3.10 o posterior. Instala Python y habilita su acceso desde la terminal.
    pause
    exit /b 1
  )
  python iniciar.py %*
)
if errorlevel 1 (
  echo.
  echo No se pudo iniciar. Revisa el mensaje anterior y el archivo .env de este equipo.
  pause
  exit /b 1
)
