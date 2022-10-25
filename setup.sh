#!/usr/bin/env bash
docker exec oauth-server bash -c 'composer install && chmod +x bin/console && bin/console doctrine:database:create && bin/console doctrine:schema:create && bin/console app:bootstrap && mkdir var/keys && openssl genrsa -out var/keys/private.key 2048 && openssl rsa -in var/keys/private.key -pubout -out var/keys/public.key && chmod -R 777 var/ && chmod -R 644 var/keys/*'
