## Chosen language
PHP whit lumen framework, MariaDB database

## EMAIL API 
https://www.mailjet.com/
Put api key and secret in .env file


## DOCKER Usage

Navigate in your terminal to the directory you cloned this, and spin up the containers for the web server by run the follow comands

-> docker-compose build
-> docker-compose up -d
-> docker exec -it api_php /bin/bash


## Composer required executed in docker console

Now run these commands to install dependences

-> composer install

Now rename .env.example to .env

Next step execute the migrations 

-> php artisan migrate


## Api documentation

https://fernandobonfimandrade.github.io/bitcoinAPI.github.io/
