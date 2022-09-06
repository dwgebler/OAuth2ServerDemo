FROM php:8.1-apache-buster
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -ri -e 's!DirectoryIndex !AllowOverride All!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/sites-available/*.conf
RUN a2enmod rewrite
WORKDIR /var/www/html
COPY app.php /var/www/html/app.php
COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
COPY [".htaccess", "/var/www/html/.htaccess"]
RUN composer install
# Replace localhost:8000 in app.php with oauth2serverdemo_web_1
RUN sed -i 's/localhost:8000/oauth-server/g' /var/www/html/app.php
# Replace oauth2serverdemo_web_1/authorize with localhost:8000/authorize
RUN sed -i 's/oauth-server\/authorize/localhost:8000\/authorize/g' /var/www/html/app.php
