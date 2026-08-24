FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies & clear cache
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

# Enable Apache rewrite module & suppress ServerName warning
RUN a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure DocumentRoot to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Apache/PHP production configs
COPY docker/apache-mpm-prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

# Copy Composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application code into container
COPY --chown=www-data:www-data . /var/www/html

# Create storage/bootstrap cache directories AND fix permissions BEFORE composer install
RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install Composer dependencies (package discovery runs cleanly now)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

EXPOSE 80

# Execute runtime caching, storage link, migrations, and start Apache
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link --force && php artisan migrate --force && if [ \"$RUN_SEED\" = \"true\" ]; then php artisan db:seed --force; fi && apache2-foreground"
