#!/usr/bin/env bash

set -Eeuo pipefail
set -o history -o histexpand

# shellcheck source=/dev/null
source "${NVM_DIR}/nvm.sh"

composer install

nvm exec bun install
nvm exec bun run build

symfony server:start --allow-http
