FROM php:8.2-apache

WORKDIR /var/www/html

# System deps
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev libpng-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip gd \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache config
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions + storage link - NO CACHE HERE
RUN php artisan storage:link --force || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

EXPOSE 80

# IMPORTANT: clear cache on every start, then migrate, then start apache
CMD php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan migrate --force && apache2-foreground
