#!/bin/bash

mysql -e "drop database csbill"
php app/console doctrine:database:create -n
php app/console doctrine:migrations:migrate -n
./bin/phpunit -c app