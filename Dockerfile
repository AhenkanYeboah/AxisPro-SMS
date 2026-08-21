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

# Enable OPcache. It ships compiled into this image but is OFF by default,
# meaning every single request was recompiling the entire framework from
# source - wasted CPU and transient memory on a container that can't spare
# either. See docker/php-production.ini for the tuning.
RUN docker-php-ext-enable opcache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Suppress ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cap mpm_prefork's worker count and set memory_limit/opcache. Debian's
# stock apache2 defaults (StartServers 5, MaxRequestWorkers 150) have no
# relationship to this container's memory budget - each worker is a full
# separate PHP process, so an unbounded worker count plus no memory_limit
# was why the app was getting OOM-killed by Render (the 503/500 crashes).
# See the comments in each file for the memory-budget math.
COPY docker/apache-mpm-prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

# Copy Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Leverage Docker layer caching: Copy lockfiles first
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY --chown=www-data:www-data . /var/www/html

# Create storage and cache directories with proper permissions
RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run composer dump-autoload without executing post-autoload scripts
RUN composer dump-autoload --optimize --no-dev --no-scripts

EXPOSE 80

# Build fresh config & route caches at runtime and run migrations on every
# boot, then start Apache. Seeding is opt-in via RUN_SEED=true (it's
# idempotent - DatabaseSeeder uses firstOrCreate throughout - but there's no
# reason to hit the DB with it on every restart). The debug `ls -la` /
# `tinker --execute=var_dump(...)` calls that used to run here on every
# single boot were dev leftovers: dead weight on every restart, and one
# more thing that could stall/fail a boot and cost you a health-check
# timeout (a second flavor of 503) on top of the memory issue.
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && if [ \"$RUN_SEED\" = \"true\" ]; then php artisan db:seed --force; fi && apache2-foreground"
