FROM node:18-alpine AS frontend
WORKDIR /app
COPY mini-app/package*.json ./mini-app/
RUN cd mini-app && npm ci
COPY mini-app/ ./mini-app/
RUN cd mini-app && npm run build
# output lands at /app/public/mini-app (vite outDir: ../public/mini-app)

FROM php:8.3-fpm-alpine AS backend
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .
COPY --from=frontend /app/public/mini-app ./public/mini-app

RUN composer dump-autoload --optimize \
    && php artisan storage:link --force 2>/dev/null || true \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
