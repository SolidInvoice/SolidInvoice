#!/usr/bin/env bash

set -Eeuo pipefail
set -o history -o histexpand

# shellcheck source=/dev/null
source "${NVM_DIR}/nvm.sh"

composer install

nvm exec yarn install
nvm exec yarn build

symfony server:start --allow-http
