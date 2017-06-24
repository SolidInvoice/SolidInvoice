#!/bin/bash

echo 'Installation...' && echo -en 'travis_fold:start:installation\\r'
./bin/behat --suite=installation -n -f progress -p "$TEST_SUITE" -vvv
echo -en 'travis_fold:end:installation\\r'

echo 'Login...' && echo -en 'travis_fold:start:login\\r'
./bin/console doctrine:database:drop --force -n -q
./bin/console doctrine:database:create -n -q
./bin/console doctrine:migrations:migrate -n -q
./bin/behat --suite=login -n -f progress -p "$TEST_SUITE" -vvv
echo -en 'travis_fold:end:login\\r'

echo 'API...' && echo -en 'travis_fold:start:api\\r'
./bin/console doctrine:database:drop --force -n -q
./bin/console doctrine:database:create -n -q
./bin/console doctrine:migrations:migrate -n -q
./bin/behat --suite=api -n -f progress -p "$TEST_SUITE" -vvv
\echo -en 'travis_fold:end:api\\r'