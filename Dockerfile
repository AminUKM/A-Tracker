FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy PHP files
COPY template/ /var/www/html/

# Copy static files
COPY static/ /var/www/html/static/

# Set permissions (optional but safe)
RUN chmod -R 755 /var/www/html/static
RUN chown -R www-data:www-data /var/www/html

# Expose the default Apache port
EXPOSE 80
