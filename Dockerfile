# Use the official PHP image with Apache
FROM php:8.1-apache

# Copy project files into the container
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
