FROM php:8.1-apache-buster
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    openssl \
    && docker-php-ext-install zip \
    && pecl install xdebug && docker-php-ext-enable xdebug
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
WORKDIR /var/www/application/var/keys
RUN openssl genrsa -out private.key 2048
RUN openssl rsa -in private.key -pubout -out public.key
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN a2enmod rewrite
WORKDIR /var/www/application
ENV APACHE_DOCUMENT_ROOT /var/www/application/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/sites-available/*.conf
