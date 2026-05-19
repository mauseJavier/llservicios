# --- ETAPA 1: Vendor (PHP 8.4 para compatibilidad con tus librerías) ---

FROM php:8.4-cli-alpine AS vendor
WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Instalamos SOAP, ZIP y dependencias para que composer valide los requisitos
RUN apk add --no-cache libxml2-dev libzip-dev zip unzip \
    && docker-php-ext-install soap zip
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader --ignore-platform-req=php
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# --- ETAPA 2: Assets (Node para compilar Vite/Tailwind) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

# --- ETAPA 3: Producción (Imagen oficial PHP con Apache) ---
FROM php:8.4-apache

# 1. Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libzip-dev \
    # Dependencias para GD
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install soap pdo_mysql zip bcmath gd opcache \
    && a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

# 2. Configurar el DocumentRoot de Apache para Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Configuración de OpenSSL para AFIP (Legacy SECLEVEL=1)
RUN printf '%s\n' \
    'openssl_conf = default_conf' \
    '[default_conf]' \
    'ssl_conf = ssl_sect' \
    '[ssl_sect]' \
    'system_default = system_default_sect' \
    '[system_default_sect]' \
    'MinProtocol = TLSv1.2' \
    'CipherString = DEFAULT@SECLEVEL=1' \
    > /etc/ssl/openssl_legacy.cnf
ENV OPENSSL_CONF=/etc/ssl/openssl_legacy.cnf

# 4. Ajustes de PHP
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN printf '%s\n' \
    "opcache.enable=1" \
    "opcache.memory_consumption=256" \
    "opcache.max_accelerated_files=20000" \
    "opcache.validate_timestamps=0" \
    "opcache.revalidate_freq=0" \
    "opcache.save_comments=1" \
    > /usr/local/etc/php/conf.d/opcache.ini

# 5. Apache logs a stdout/stderr (permisos para www-data)
RUN rm -f /var/log/apache2/access.log /var/log/apache2/error.log && \
    ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log && \
    chmod 777 /var/log/apache2 /dev/stdout /dev/stderr

# 6. Copiar el código y los resultados de las etapas anteriores
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# 7. Preparar rutas de runtime y permisos correctos para Laravel
RUN mkdir -p /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && touch /var/www/html/storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Cache de Laravel (produccion)
# Deshabilitado para desarrollo - se ejecutará en runtime si es necesario
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage \
    && php artisan view:cache 

HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD php /var/www/html/artisan up || exit 1


EXPOSE 443

# USER www-data