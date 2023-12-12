#!/bin/sh
set -e
 
echo "Deploying Production application ..."

# Enter maintenance mode
(php artisan down --render="maintenance") || true
    # Update codebase
    git fetch origin production
    git reset --hard origin/production
 
    # Install dependencies based on lock file
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    
    # Migrate database
    php artisan migrate --force
 
    # Note: If you're using queue workers, this is the place to restart them.
    php artisan queue:restart

    php artisan horizon:terminate

#    sudo supervisorctl reread
#    sudo supervisorctl update
#    sudo supervisorctl start laravel-queue-worker:*
#    sudo supervisorctl start laravel-queue-sitemap-worker:*
#    sudo supervisorctl start horizon

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
