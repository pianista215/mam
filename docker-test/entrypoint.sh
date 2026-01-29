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

echo "Loading analysis attributes from /analysis.sql"
mysql yii2db < /analysis.sql

echo "Loading config from /config.sql"
mysql yii2db < /config.sql

echo "Loading countries from /countries.sql"
mysql yii2db < /countries.sql

echo "Loading issues types from /issues.sql"
mysql yii2db < /issues.sql

echo "Loading pages from /pages.sql"
mysql yii2db < /pages.sql

echo "Loading statistics types from /statistics.sql"
mysql yii2db < /statistics.sql

echo "Overwritting db conf for Yii"
cp /db-docker.php /var/www/html/config/db.php

echo "Executing migrations"
php /var/www/html/yii migrate/up --interactive=0

echo "RBAC Migrations."
php /var/www/html/yii migrate-rbac --interactive=0

echo "Custom-rbac migrations"
php /var/www/html/yii migrate-custom-rbac --interactive=0

echo "Load flags"
php /var/www/html/yii flags-load/load /tmp/initial_flags/

# Load test fixtures
php /var/www/html/yii fixture "AuthAssignment, SubmittedFlightPlan" --namespace='tests\fixtures' --interactive=0

#Init apache in foreground
apache2-foreground