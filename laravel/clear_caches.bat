@echo off
echo Clearing Laravel caches...
php artisan route:clear
php artisan config:clear
php artisan cache:clear
echo Done! Routes and caches cleared.
pause

