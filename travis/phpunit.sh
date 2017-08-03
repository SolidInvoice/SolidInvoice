#!/bin/bash

php bin/console doctrine:migrations:migrate -n -q

./bin/phpunit --coverage-clover build/logs/clover.xml
