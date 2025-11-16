#!/usr/bin/env bash

set -euo pipefail

if ! command -v yq &> /dev/null; then
    echo "yq could not be found, please install it to proceed."
    exit 1
fi

for item_name in $(yq '.[]' config/locales.yaml); do
  bin/console translation:extract "$item_name" --force --domain=messages --format=xlf20 --no-fill
  bin/console translation:extract "$item_name" --force --domain=email --format=xlf20 --no-fill
done
