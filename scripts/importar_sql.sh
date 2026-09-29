#!/bin/bash

# Variables (ajusta según tu entorno)
ARCHIVO="/home/mause/Downloads/llservicios.sql"
CONTENEDOR=mariadb
USUARIO=root
PASSWORD=root
BASE=localllservicios

# Copiar el archivo al contenedor
docker cp "$ARCHIVO" "$CONTENEDOR":/tmp/mibasedatos.sql


# Borrar la base de datos y crearla nuevamente
docker exec -i "$CONTENEDOR" sh -c "mariadb -u$USUARIO -p$PASSWORD -e 'DROP DATABASE IF EXISTS $BASE; CREATE DATABASE $BASE;'"

# Ejecutar la importación dentro del contenedor
docker exec -i "$CONTENEDOR" sh -c "mariadb -u$USUARIO -p$PASSWORD $BASE < /tmp/mibasedatos.sql"

# (Opcional) Borrar el archivo dentro del contenedor
docker exec "$CONTENEDOR" rm /tmp/mibasedatos.sql

echo "✅ Importación completada."