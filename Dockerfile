FROM php:8.2-apache

# Set environment variables for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies & clean up apt cache to save image space
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

# Install PHP extensions required for PostgreSQL and Laravel
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Enable Apache rewrite + update DocumentRoot to /public
RUN a2enmod rewrite && \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Copy Composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Leverage Docker Layer Caching:
# Copy dependency manifests first so composer install only runs when lockfile changes
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . /var/www/html

# Generate optimized autoloader now that full codebase is present
RUN composer dump-autoload --optimize --no-dev

# Set directory permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Run caching, migrations, and start Apache dynamically at runtime
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground"
