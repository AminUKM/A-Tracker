# Use official PHP image with Apache
FROM php:8.2-apache

# Copy your PHP project files into Apache root
COPY . /var/www/html/

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
