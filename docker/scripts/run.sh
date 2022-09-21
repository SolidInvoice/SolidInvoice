#!/usr/bin/env bash

set -Eeuxo pipefail
set -o history -o histexpand

# run nginx & php-fpm
php-fpm -D -R

nginx -g "daemon off;"
