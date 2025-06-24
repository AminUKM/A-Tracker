FROM php:8.2-apache

# Enable PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy files
COPY template/ /var/www/html/            # PHP files
COPY static/ /var/www/html/static/       # Images and assets

# Permissions (optional but good)
RUN chmod -R 755 /var/www/html/static
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
