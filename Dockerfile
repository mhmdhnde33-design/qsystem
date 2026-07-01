FROM php:8.1-apache

# تثبيت امتداد PDO MySQL
RUN docker-php-ext-install pdo_mysql

# تفعيل mod_rewrite إن لزم
RUN a2enmod rewrite

WORKDIR /var/www/html
