#!/bin/sh
set -e
 
echo "Deploying application ..."
 
# Enter maintenance mode
(php artisan down) || true
    # Update codebase
    git fetch origin development
    git reset --hard origin/development
 
    # Install dependencies based on lock file
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    
    composer dump-autoload
 
    # Migrate database
    php artisan migrate --force
 
    # Note: If you're using queue workers, this is the place to restart them.
#    sudo supervisorctl reread
#    sudo supervisorctl update
#    sudo supervisorctl start laravel-queue-worker:*
#    sudo supervisorctl start laravel-queue-sitemap-worker:*

    
    php artisan hostelz:clearPageCache
 
    # Clear cache
    php artisan optimize:clear

    # built assets
    npm install --no-audit
    npm run prod
 
    # Reload PHP to update opcache
    #echo "" | sudo -S service php7.4-fpm reload
# Exit maintenance mode
php artisan up
 
echo "Application deployed!"
