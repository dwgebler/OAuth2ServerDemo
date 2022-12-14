# Build your own OAuth2 Server with Symfony and PHP

This is the sample app accompanying my blog post at https://davegebler.com/post/php/build-oauth2-server-php-symfony

## Setup instructions - Docker

1. Clone this repo
2. Ensure you have Docker Engine >= 17.05 installed. https://docs.docker.com/get-docker/
3. Run `docker compose up -d`
4. Run the included `setup.sh` script to install dependencies and create the database.
5. Visit http://localhost:8080 in your browser and click the single sign-on link to see the app in action.
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
9. Run `bin/console doctrine:schema:create` to create the database tables.
9. Run `bin/console app:bootstrap` to bootstrap the database with the demo data.
   1. Run `bin/console app:bootstrap --help` for more options.
   2. Otherwise the default user is `me@davegebler.com` with password `password`.
10. Run `symfony server:start -d` to start the server in the background on port 8000.
11. From the `client` directory, run `php -S localhost:8080 app.php` to start the client on port 8080.
12. You may need to edit the `client/app.php` file to change the URI variables to match the URL and port of your local server.
    1. For example, your Symfony server may use https://localhost:8000 instead of http://localhost:8000.
13. Visit http://localhost:8080 in your browser and click the single sign-on link to see the app in action.
