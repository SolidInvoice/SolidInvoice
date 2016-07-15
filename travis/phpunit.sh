#!/bin/bash

mysql -e "drop database csbill"

php bin/console doctrine:database:create -n
php bin/console doctrine:migrations:migrate -n -q

./bin/phpunit --coverage-clover build/logs/clover.xml
