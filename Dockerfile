# Usar la imagen oficial de PHP con FPM
FROM php:8.2-fpm

# Instala extensiones de PHP necesarias para Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Configurar permisos
#  RUN chown -R www-data:www-data /var/www/html \
#      && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Instala las dependencias del proyecto
# RUN composer install --no-scripts --no-autoloader

