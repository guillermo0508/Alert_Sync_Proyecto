FROM php:8.4-fpm-alpine

# Environment variables for Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies and Nginx
RUN apk add --no-cache \
    nginx \
    curl \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql bcmath opcache zip intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies without dev dependencies and without running scripts during build
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx and entrypoint scripts
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

