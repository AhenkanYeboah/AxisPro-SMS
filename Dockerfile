FROM php:8.2-apache

WORKDIR /var/www/html

# System deps
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev libpng-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip gd \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache to serve from public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy code first
COPY . .

# FIX: Create writable dirs BEFORE composer install
RUN mkdir -p bootstrap/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    storage/app/public \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Now install - will not fail
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer run-script post-autoload-dump --no-interaction || true \
    && php artisan package:discover --ansi || true \
    && php artisan storage:link --force || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Clear cache on start + migrate + start apache
CMD php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan migrate --force && apache2-foreground
