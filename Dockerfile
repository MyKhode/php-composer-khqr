# PHP 8.2 with Apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev git unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo pdo_mysql

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# ✅ Add this line to apply php.ini overrides
COPY php.ini /usr/local/etc/php/conf.d/uploads.ini

# Fix git “dubious ownership” + refresh lock to include Phinx
RUN git config --global --add safe.directory /var/www/html \
 && composer update --no-dev --no-interaction --prefer-dist

# Enable Apache rewrite; serve /public
RUN a2enmod rewrite \
 && sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]
