#!/usr/bin/env bash

set -euo pipefail

sphinx-autobuild --host 0.0.0.0 --port 9000 -d ./_build/doctrees -b html --watch ./theme --watch ./theme/static/css --watch ./theme/static/js -a ./source ./_build/html
