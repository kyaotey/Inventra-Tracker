FROM php:8.1-apache

# Fix Apache "ServerName" warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable mysqli extension (important for DB)
RUN docker-php-ext-install mysqli

# Copy project files into Apache directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
