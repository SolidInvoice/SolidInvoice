#!/usr/bin/env bash

set -Eeuxo pipefail
set -o history -o histexpand

SOLIDINVOICE_ENV=test SOLIDINVOICE_DEBUG=0 php bin/console doctrine:migrations:migrate -n -q

SKIP_FUNCTIONAL_BOOTSTRAP=1 ./bin/simple-phpunit --coverage-clover build/logs/clover.xml --exclude-group installation,functional

exit $?
