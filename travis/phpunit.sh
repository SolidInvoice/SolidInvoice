mysql -e "drop database csbill"
php app/console doctrine:database:create -n
php app/console doctrine:migrations:migrate -n
php app/console doctrine:fixtures:load -n
./bin/phpunit -c app