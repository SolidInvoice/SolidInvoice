#!/bin/bash

mysql -e "drop database csbill"
php app/console doctrine:database:create -n
php app/console doctrine:migrations:migrate -n -q
./bin/phpunit -c app --coverage-clover build/logs/clover.xml
