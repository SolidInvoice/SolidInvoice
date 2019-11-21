#!/bin/bash

php bin/console doctrine:migrations:migrate -n -q

./bin/simple-phpunit --coverage-clover build/logs/clover.xml
