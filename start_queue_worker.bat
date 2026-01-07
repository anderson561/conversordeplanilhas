@echo off
echo Starting Queue Worker...
cd /d C:\Users\ANDERSON\php
:loop
php artisan queue:work database --tries=3 --timeout=90 --sleep=3 --max-jobs=100
echo Queue worker stopped. Restarting in 5 seconds...
timeout /t 5
goto loop
