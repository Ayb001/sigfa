FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip unzip git \
    freetype-dev \
    libjpeg-turbo-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

RUN composer run-script post-autoload-dump 2>/dev/null || true \
  && php artisan config:cache \
  && php artisan route:cache \
  && php artisan view:cache \
  && chown -R www-data:www-data storage bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
