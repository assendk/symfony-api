Very basic Symfony 5.4 api
with 2 end points
Requirements:
- php7.4+
- docker and docker-compose or mariadb10.5.8+

Run:
- composer install
- setup the database in the .env file
- php bin/console make:migration
- php bin/console doctrine:migrations:migrate