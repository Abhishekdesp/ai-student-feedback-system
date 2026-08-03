FROM php:8.2-apache

# Install required MySQLi & PDO extensions for PHP database connections
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Set Apache DirectoryIndex to serve login.php as the home page
RUN echo "DirectoryIndex login.php index.php index.html" >> /etc/apache2/apache2.conf

# Copy application files from login module directly to Apache root
COPY ["./login module/", "/var/www/html/"]

# Enable Apache rewrite module
RUN a2enmod rewrite

EXPOSE 80
