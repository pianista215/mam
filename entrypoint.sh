#!/bin/bash

# Iniciar MariaDB en segundo plano
service mariadb start

# Crear la base de datos y usuario iniciales
mysql -e "CREATE DATABASE IF NOT EXISTS yii2db;"
mysql -e "CREATE USER IF NOT EXISTS 'yii2user'@'%' IDENTIFIED BY 'yii2password';"
mysql -e "GRANT ALL PRIVILEGES ON yii2db.* TO 'yii2user'@'%';"
mysql -e "FLUSH PRIVILEGES;"

# Crear el esquema desde db-model/ddl.sql (si existe)
if [ -f "/app/db-model/ddl.sql" ]; then
  echo "Aplicando esquema desde db-model/ddl.sql..."
  mysql yii2db < /app/db-model/ddl.sql
fi

if [ -f "/app/db-docker.php" ]; then
  echo "Usando configuración de base de datos para Docker..."
  cp /app/db-docker.php /var/www/html/config/db.php
  cp /app/db-docker.php /app/basic/config/db.php
fi

# Ejecutar migraciones desde el directorio correcto
if [ -f "/app/basic/yii" ]; then
  echo "Ejecutando migraciones principales..."
  php /app/basic/yii migrate/up --interactive=0

  echo "Ejecutando migraciones de RBAC..."
  php /app/basic/yii migrate-rbac --interactive=0

  echo "Ejecutando migraciones personalizadas de RBAC..."
  php /app/basic/yii migrate-custom-rbac --interactive=0
fi

# Iniciar Apache en primer plano
apache2-foreground