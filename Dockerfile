FROM php:8.2-apache

# Install MySQL PDO extension and other useful modules
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy project files to Apache's root directory
COPY template/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

EXPOSE 80
