@echo off
REM Script de configuración rápida para el sistema de colas (Windows)
REM Autor: Sistema de Validación de Clientes
REM Fecha: 2024

echo ==========================================
echo   Configuración de Sistema de Colas
echo ==========================================
echo.

:menu
echo Selecciona el entorno:
echo 1) Desarrollo (sync - sin worker)
echo 2) Producción (database - con worker)
echo.
set /p option="Opción (1 o 2): "

if "%option%"=="1" goto desarrollo
if "%option%"=="2" goto produccion
echo Opción inválida
goto end

:desarrollo
echo.
echo Configurando para DESARROLLO...
echo.

REM Configurar sync en .env
findstr /C:"QUEUE_CONNECTION=" .env >nul 2>&1
if %errorlevel%==0 (
    powershell -Command "(gc .env) -replace 'QUEUE_CONNECTION=.*', 'QUEUE_CONNECTION=sync' | Out-File -encoding ASCII .env"
    echo [OK] QUEUE_CONNECTION actualizado a 'sync'
) else (
    echo QUEUE_CONNECTION=sync >> .env
    echo [OK] QUEUE_CONNECTION agregado como 'sync'
)

REM Limpiar caché
php artisan config:clear >nul 2>&1
echo [OK] Caché de configuración limpiado

echo.
echo [OK] Configuración completada!
echo.
echo Con 'sync', los jobs se ejecutan inmediatamente sin necesidad de worker.
echo Esto es ideal para desarrollo y testing.
echo.
goto end

:produccion
echo.
echo Configurando para PRODUCCIÓN...
echo.

REM Configurar database en .env
findstr /C:"QUEUE_CONNECTION=" .env >nul 2>&1
if %errorlevel%==0 (
    powershell -Command "(gc .env) -replace 'QUEUE_CONNECTION=.*', 'QUEUE_CONNECTION=database' | Out-File -encoding ASCII .env"
    echo [OK] QUEUE_CONNECTION actualizado a 'database'
) else (
    echo QUEUE_CONNECTION=database >> .env
    echo [OK] QUEUE_CONNECTION agregado como 'database'
)

REM Crear tabla de jobs
echo.
echo Creando tabla de jobs...
php artisan queue:table >nul 2>&1
echo [OK] Migración de tabla de jobs creada

REM Ejecutar migraciones
echo.
echo Ejecutando migraciones...
php artisan migrate --force
echo [OK] Migraciones ejecutadas

REM Limpiar caché
php artisan config:clear >nul 2>&1
echo [OK] Caché de configuración limpiado

echo.
echo [OK] Configuración completada!
echo.
echo [IMPORTANTE] Debes iniciar el queue worker:
echo.
echo   php artisan queue:work --tries=3 --timeout=300
echo.
echo O configurar un servicio para mantenerlo corriendo en producción.
echo Ver SOLUCION_TIMEOUT_USUARIOS.md para más detalles.
echo.
goto end

:end
echo ==========================================
echo   Verificación de Configuración
echo ==========================================
echo.

REM Mostrar configuración actual
for /f "tokens=2 delims==" %%a in ('findstr "QUEUE_CONNECTION=" .env') do set QUEUE_CONN=%%a
echo Driver de colas: %QUEUE_CONN%

if "%QUEUE_CONN%"=="database" (
    echo.
    echo [IMPORTANTE] Recuerda iniciar el worker:
    echo   php artisan queue:work
)

echo.
echo ==========================================
echo   ¡Listo para usar!
echo ==========================================
echo.
echo Ahora puedes crear usuarios sin problemas de timeout.
echo Los productos se copiarán en segundo plano.
echo.
pause
