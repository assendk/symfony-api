# Very basic Symfony 5.4 api
With 2 end points - insert and read
## Requirements:
- php7.4+
- docker and docker-compose or mariadb10.5.8+
- if you have the symfony binary installed you can run "symfony check:requirements"

## Run:
- composer install
- generate you own APP_SECRET in .env (*read below how)
- setup the database in the .env file or use "docker-compose up -d" in the project root with the provided demo (change the password)
- php bin/console make:migration
- php bin/console doctrine:migrations:migrate
- php bin/console server:start

## Usage:
### Insert
- https://127.0.0.1:8001/api/insert, set POST (change the port in the url, check the https)
- add a json string in the body
  {"title": "Test title", "content": "Test article body", "created_at": "2023-01-01 01:00:00", "publish_at": "2023-02-01 02:00:00", "status": "active"}
  
### Read example
- https://127.0.0.1:8001/api/show/all/{format} show all
- https://127.0.0.1:8001/api/show/active/{format} filter only with status active
- Supported formats are: json, xml, csv'
- format is optional and json will be used if not set
  
- With pagination test https://127.0.0.1:8001/api/show/paginate?page=2&limit=2
  page=2 is the number of the page to display
  limit=2 how many articles per page to display
  
## To generate you own Auth key*
### Using OpenSSL
openssl rand -hex 32
### Using PHP
php -r 'echo bin2hex(random_bytes(32)) . PHP_EOL;'

  
