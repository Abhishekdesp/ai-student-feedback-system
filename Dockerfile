FROM php:8.2-apache
COPY ["./login module/", "/var/www/html/"]
EXPOSE 80
