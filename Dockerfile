FROM php:8.2-apache

# Set non-interactive mode for apt operations
ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies & clear apt cache to save image space
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

# Install PHP extensions required for PostgreSQL + Laravel
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Enable Apache rewrite module
RUN a2enmod rewrite

# Suppress ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Leverage Docker layer caching: Copy lockfiles first
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy remaining application code
COPY . /var/www/html

# 1. Ensure storage and bootstrap/cache directories exist with proper permissions
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache \
    && chmod -R 777 bootstrap/cache storage

# 2. Now run dump-autoload safely
RUN composer dump-autoload --optimize --no-dev

# Copy application code
COPY --chown=www-data:www-data . /var/www/html

# Ensure storage directories exist and have proper permissions
RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
EXPOSE 80

# Build fresh config & route caches at runtime when environment variables are present
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground"
