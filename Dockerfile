# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory inside container
WORKDIR /var/www/html

# Copy all files to the container
COPY . .

# Install PHP dependencies
RUN composer install

# Expose port 80
EXPOSE 80
