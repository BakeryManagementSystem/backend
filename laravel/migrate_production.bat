@echo off
echo ========================================
echo PRODUCTION Database Migration Script
echo ========================================
echo.
echo WARNING: This will reset your PRODUCTION database!
echo Make sure you have a backup before proceeding.
echo.
pause
echo.

echo Step 1: Checking database connection...
php artisan db:show
echo.

echo Step 2: Clearing all caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo.

echo Step 3: Running fresh migrations (will drop all tables)...
php artisan migrate:fresh --force
echo.

echo Step 4: Seeding database with initial data...
php artisan db:seed --force
echo.

echo Step 5: Optimizing application...
php artisan optimize
echo.

echo ========================================
echo Production Database Migration Complete!
echo ========================================
echo.
echo Please verify:
echo 1. Check categories API: /api/categories
echo 2. Check products API: /api/products
echo 3. Test user login
echo 4. Verify all tables exist
echo.
pause

