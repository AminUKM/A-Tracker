FROM php:8.2-apache

# Enable PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy all files to Apache root
COPY template/ /var/www/html/

# Set correct permissions
RUN chmod -R 755 /var/www/html/static
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

