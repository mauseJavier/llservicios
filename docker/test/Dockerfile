# Imagen de test: PHP 8.4 CLI con las extensiones que usa la aplicación
# (SOAP/ARCA, ZIP, GD/PDF-QR y MySQL), más Composer.
FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libxml2-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install soap pdo_mysql zip bcmath gd \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
    "memory_limit = 512M" \
    > /usr/local/etc/php/conf.d/zz-testing.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
