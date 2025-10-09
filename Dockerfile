# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install required PHP extensions (GD, PDO, etc.)
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 9005
EXPOSE 9005

# Run built-in PHP server
CMD ["php", "-S", "0.0.0.0:9005", "khqr_api.php"]
