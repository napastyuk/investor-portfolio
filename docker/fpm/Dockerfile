FROM php:8.3.23-fpm-alpine

WORKDIR /var/www

# Устанавливаем зависимости
RUN apk add --no-cache unzip curl postgresql-dev

# Установка расширения PostgreSQL
RUN docker-php-ext-install pdo_pgsql

RUN mkdir -p /var/www/logs && chown www-data:www-data /var/www/logs

# Копирование и установка сomposer из официального образа
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-plugins --no-scripts

VOLUME /data

CMD ["php-fpm"]
