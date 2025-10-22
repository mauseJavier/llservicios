# Usar la imagen base de shinsenter/laravel
FROM shinsenter/laravel:latest

# Instalar mariadb-client
RUN apt-get update && apt-get install -y mariadb-client


# Puedes agregar otros comandos aquí si es necesario

# RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# RUN php artisan storage:link