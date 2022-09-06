mkdir -p var/keys
openssl genrsa -out var/keys/private.key 2048
openssl rsa -in var/keys/private.key -pubout -out var/keys/public.key
docker exec oauth2-server bash -c 'composer install && bin/console doctrine:database:create && bin/console app:bootstrap'