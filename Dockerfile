FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install core dependencies & clear cache
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd \
    && docker-php-ext-enable opcache

# Enable Apache rewrite module and suppress ServerName warning
RUN a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy production configs
COPY docker/apache-mpm-prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

# Copy Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Leverage layer caching: Copy lockfiles first
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY --chown=www-data:www-data . /var/www/html

# Create storage and bootstrap cache directories with permissions
RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link --force && php artisan migrate --force && if [ \"$RUN_SEED\" = \"true\" ]; then php artisan db:seed --force; fi && apache2-foreground"
