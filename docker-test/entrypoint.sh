#!/bin/bash

# Init mariadb
service mariadb start

#Create database and load schema and migrations
mysql -e "CREATE DATABASE IF NOT EXISTS yii2db;"
mysql -e "CREATE USER IF NOT EXISTS 'yii2user'@'%' IDENTIFIED BY 'yii2password';"
mysql -e "GRANT ALL PRIVILEGES ON yii2db.* TO 'yii2user'@'%';"
mysql -e "FLUSH PRIVILEGES;"

echo "Loading schema from /ddl.sql..."
mysql yii2db < /ddl.sql

echo "Overwritting db conf for Yii"
cp /db-docker.php /var/www/html/config/db.php

echo "Executing migrations"
php /var/www/html/yii migrate/up --interactive=0

echo "RBAC Migrations."
php /var/www/html/yii migrate-rbac --interactive=0

echo "Custom-rbac migrations"
php /var/www/html/yii migrate-custom-rbac --interactive=0

# Load test fixtures
php /var/www/html/yii fixture "AuthAssignment, SubmittedFlightPlan" --namespace='tests\fixtures' --interactive=0

#Init apache in foreground
apache2-foreground