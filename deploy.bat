@echo off
echo ======================================
echo   Bakery Management System - Backend
echo   Docker Deployment Script
echo ======================================

:menu
echo.
echo Please select an option:
echo 1. Build and start containers
echo 2. Stop containers
echo 3. Restart containers
echo 4. View logs
echo 5. Run database migrations
echo 6. Generate application key
echo 7. Clear cache
echo 8. Access container shell
echo 9. Exit
echo.
set /p choice="Enter your choice (1-9): "

if "%choice%"=="1" goto build_start
if "%choice%"=="2" goto stop
if "%choice%"=="3" goto restart
if "%choice%"=="4" goto logs
if "%choice%"=="5" goto migrate
if "%choice%"=="6" goto generate_key
if "%choice%"=="7" goto clear_cache
if "%choice%"=="8" goto shell
if "%choice%"=="9" goto exit
echo Invalid choice. Please try again.
goto menu

:build_start
echo Building and starting containers...
copy .env.docker laravel\.env
docker-compose up --build -d
echo.
echo Containers started successfully!
echo Application: http://localhost:8000
echo phpMyAdmin: http://localhost:8080
echo.
pause
goto menu

:stop
echo Stopping containers...
docker-compose down
echo Containers stopped.
pause
goto menu

:restart
echo Restarting containers...
docker-compose restart
echo Containers restarted.
pause
goto menu

:logs
echo Displaying application logs...
docker-compose logs -f app
goto menu

:migrate
echo Running database migrations...
docker-compose exec app php artisan migrate --force
echo Migrations completed.
pause
goto menu

:generate_key
echo Generating application key...
docker-compose exec app php artisan key:generate --force
echo Application key generated.
pause
goto menu

:clear_cache
echo Clearing application cache...
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
echo Cache cleared.
pause
goto menu

:shell
echo Accessing container shell...
docker-compose exec app bash
goto menu

:exit
echo Goodbye!
exit
