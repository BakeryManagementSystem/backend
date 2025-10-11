@echo off
echo ========================================
echo Database Reset and Seed Script
echo ========================================
echo.

echo Step 1: Dropping all tables...
php artisan db:wipe --force
echo.

echo Step 2: Running migrations...
php artisan migrate --force
echo.

echo Step 3: Running seeders...
php artisan db:seed --force
echo.

echo ========================================
echo Database reset and seeding complete!
echo ========================================
pause

