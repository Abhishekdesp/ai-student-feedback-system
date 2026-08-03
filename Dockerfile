FROM php:8.2-apache

# Install required MySQLi extension for PHP database connections
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Copy application files from login module to Apache root
COPY ["./login module/", "/var/www/html/"]

# Enable Apache rewrite module
RUN a2enmod rewrite

EXPOSE 80
