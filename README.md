# Build your own OAuth2 Server with Symfony and PHP

This is the sample app accompanying my blog post at https://davegebler.com/

## Setup instructions - Docker

1. Clone this repo
2. Ensure you have Docker Engine >= 17.05 installed. https://docs.docker.com/get-docker/
3. Run `docker compose up -d`
5. Run `docker exec -ti oauth2serverdemo_web_1 composer install`
6. Run `docker exec -ti oauth2serverdemo_web_1 bin/console doctrine:database:create`
7. Run `docker exec -ti oauth2serverdemo_web_1 bin/console app:bootstrap`
8. Visit http://localhost:8080 in your browser and click the single sign-on link to see the app in action.
   1. The username is `me@davegebler.com` and the password is `password`

## Setup instructions - manual

1. Ensure you have [PHP](https://www.php.net/downloads.php) >= 8.1 installed, as well as the [Symfony CLI](https://symfony.com/download), [Composer](https://getcomposer.org) and OpenSSL.
2. Clone the repo
3. Run `composer install` in both the main project dir and the `client` dir
4. In the project dir, ensure the directory `var` is crated and is writable.
5. Ensure the directory `var/keys` is created.
6. Generate your keys using OpenSSL from inside the `var/keys` directory:
   1. `openssl genrsa -out private.key 2048`
   2. `openssl rsa -in private.key -pubout -out public.key`
7. If necessary, make the `bin/console` script executable: `chmod +x bin/console`
8. Run `bin/console doctrine:database:create` to create the SQLite database.
9. Run `bin/console app:bootstrap` to bootstrap the database with the demo data.
   1. Run `bin/console app:bootstrap --help` for more options.
   2. Otherwise the default user is `me@davegebler.com` with password `password`.
10. Run `symfony server:start -d` to start the server in the background on port 8000.
11. From the `client` directory, run `php -S localhost:8080 app.php` to start the client on port 8080.
12. Visit http://localhost:8080 in your browser and click the single sign-on link to see the app in action.
