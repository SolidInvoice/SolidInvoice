#!/bin/bash

php bin/console doctrine:migrations:migrate -n

./bin/phpunit --coverage-clover build/logs/clover.xml
