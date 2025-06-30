FROM php:8.1-apache

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable MySQL extension
RUN docker-php-ext-install mysqli

# Copy all files
COPY . /var/www/html/

# Fix ownership (permissions)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
