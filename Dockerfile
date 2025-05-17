# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create both uploads directories
RUN mkdir -p /var/www/html/uploads/charge-points && \
    mkdir -p /var/www/html/uploads/charge_points

# Change ownership to the web server user (www-data)
RUN chown -R www-data:www-data /var/www/html/uploads

# Set permissions
RUN chmod -R 755 /var/www/html/uploads

# Copy all files
COPY . .

# Install PHP packages
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache

# Expose port 80
EXPOSE 80
