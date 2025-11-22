# Docker for Acars testing purposes, don't use in production
FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    libzip-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql zip gd \
    && apt-get clean

RUN mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld

COPY basic /var/www/html

COPY docker-test/apache2-default.conf /etc/apache2/sites-available/000-default.conf

COPY db-model/ddl.sql /
COPY db-model/analysis.sql /
COPY db-model/config.sql /
COPY db-model/countries.sql /
COPY db-model/issues.sql /
COPY db-model/pages.sql /
COPY docker-test/db-docker.php /

RUN mkdir -p /opt/mam/chunks \
    && mkdir -p /opt/mam/images/rank_icon \
    && mkdir -p /opt/mam/images/pilot_profile \
    && mkdir -p /opt/mam/images/tour_image \
    && mkdir -p /opt/mam/images/country_icon \
    && mkdir -p /opt/mam/images/aircraftType_image \
    && mkdir -p /opt/mam/images/page_image \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /opt/mam/chunks \
    && chown -R www-data:www-data /opt/mam/images \
    && chmod -R 755 /var/www/html \
    && chmod -R 755 /opt/mam/chunks

COPY initial_flags /tmp/initial_flags

RUN a2enmod rewrite


EXPOSE 80

EXPOSE 3306

COPY docker-test/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]