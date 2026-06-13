docker exec -it localllservicios-v2 php artisan session:table

docker exec -it localllservicios-v2 php artisan migrate

 Cambiar la configuración en .env

 SESSION_DRIVER=database

 docker exec -it localllservicios-v2 php artisan config:clear

 